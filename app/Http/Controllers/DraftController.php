<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Duel;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Models\User;
use App\Services\DraftService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DraftController extends Controller
{
    public function __construct(private DraftService $draft) {}

    public function show(Request $request, Duel $duel): View|RedirectResponse
    {
        $user = $request->user();
        $this->guardParticipant($user, $duel);

        $this->draft->tick($duel);
        $duel->refresh();

        if ($duel->status !== Duel::STATUS_DRAFTING) {
            return $this->redirectTo($duel);
        }

        $round = $this->draft->ensureRound($duel);

        if (! $round) {
            return $this->redirectTo($duel);
        }

        $state = $this->payload($duel, $user, $round);

        return view('duels.draft', [
            'duel' => $duel,
            'user' => $user,
            'opponent' => $this->opponentOf($duel, $user),
            'round' => $round,
        ] + $state);
    }

    public function store(Request $request, Duel $duel): RedirectResponse
    {
        $user = $request->user();
        $this->guardParticipant($user, $duel);

        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        try {
            $this->draft->act($duel, (int) $user->id, (int) $data['course_id']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('duels.draft', $duel)
            ->with('success', $this->feedback($data['course_id']));
    }

    public function state(Request $request, Duel $duel): JsonResponse
    {
        $user = $request->user();
        $this->guardParticipant($user, $duel);

        $this->draft->tick($duel);
        $duel->refresh();

        if ($duel->status !== Duel::STATUS_DRAFTING) {
            return response()->json([
                'done' => true,
                'status' => $duel->status,
                'redirect' => $this->redirectTarget($duel),
            ]);
        }

        $round = $this->draft->ensureRound($duel);

        if (! $round) {
            return response()->json([
                'done' => true,
                'status' => $duel->status,
                'redirect' => $this->redirectTarget($duel),
            ]);
        }

        return response()->json(
            ['done' => false, 'status' => $duel->status] + $this->payload($duel, $user, $round)
        );
    }

    private function payload(Duel $duel, User $user, DuelRound $round): array
    {
        $plan = $this->draft->roundPlan($duel);
        $order = $this->draft->orderForRound($round);
        $step = (int) $duel->draft_step;
        $running = $step < $plan['steps'];
        $action = $running ? $this->draft->stepAction($step, $plan['bans']) : null;
        $turnUserId = $running ? ($order[$step] ?? null) : null;

        $history = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->with(['user:id,name', 'course:id,name,code,semester'])
            ->orderBy('round_number')
            ->orderBy('step')
            ->get()
            ->map(fn (DuelDraft $draft) => [
                'id' => (int) $draft->id,
                'round' => (int) $draft->round_number,
                'step' => (int) $draft->step,
                'action' => $draft->action,
                'is_auto' => (bool) $draft->is_auto,
                'auto_reason' => $draft->auto_reason,
                'user_id' => (int) $draft->user_id,
                'user_name' => $draft->user?->name,
                'course_id' => (int) $draft->course_id,
                'course_name' => $draft->course?->name,
            ])
            ->values();

        $opponent = $this->opponentOf($duel, $user);

        $finished = DuelRound::query()
            ->where('duel_id', $duel->id)
            ->where('status', DuelRound::STATUS_FINISHED)
            ->orderBy('round_number')
            ->get(['round_number', 'winner_id']);
        $roundWins = $finished->whereNotNull('winner_id')->pluck('winner_id')->countBy();
        $previous = $finished->last();

        return [
            'current_round' => (int) $duel->current_round,
            'max_round' => (int) $duel->max_rounds,
            'step' => $step,
            'steps' => $plan['steps'],
            'bans' => $plan['bans'],
            'action' => $action,
            'order' => $order,
            'turn_user_id' => $turnUserId,
            'turn_user_name' => $turnUserId === null
                ? null
                : User::query()->whereKey($turnUserId)->value('name'),
            'is_my_turn' => $turnUserId === (int) $user->id,
            'deadline' => $duel->turn_deadline?->toIso8601String(),
            'seconds_left' => $this->secondsLeft($duel),
            'pool' => $this->draft->poolState($duel),
            'history' => $history,
            'last_draft_id' => $history->last()['id'] ?? 0,
            'picks' => $history->where('action', DuelDraft::ACTION_PICK)->groupBy('round'),
            'round_wins' => [
                $roundWins[(int) $user->id] ?? 0,
                $roundWins[(int) $opponent->id] ?? 0,
            ],
            'previous_round' => $previous ? [
                'number' => (int) $previous->round_number,
                'winner_id' => $previous->winner_id === null ? null : (int) $previous->winner_id,
            ] : null,
            'is_room' => ! empty($duel->challenge?->course_ids),
        ];
    }

    private function secondsLeft(Duel $duel): int
    {
        if ($duel->turn_deadline === null || $duel->draft_step >= $this->draft->steps($duel)) {
            return 0;
        }

        return max(0, $duel->turn_deadline->getTimestamp() - now()->getTimestamp());
    }

    private function guardParticipant(User $user, Duel $duel): void
    {
        abort_unless(
            (int) $duel->player1_id === (int) $user->id || (int) $duel->player2_id === (int) $user->id,
            403
        );
    }

    private function opponentOf(Duel $duel, User $user): User
    {
        return (int) $duel->player1_id === (int) $user->id ? $duel->player2 : $duel->player1;
    }

    private function redirectTo(Duel $duel): RedirectResponse
    {
        return redirect()->to($this->redirectTarget($duel));
    }

    private function redirectTarget(Duel $duel): string
    {
        return match (true) {
            $duel->status === Duel::STATUS_BATTLE && Route::has('duels.battle') => route('duels.battle', $duel),
            $duel->status === Duel::STATUS_FINISHED && Route::has('duels.result') => route('duels.result', $duel),
            default => route(Route::has('duels.index') ? 'duels.index' : 'dashboard'),
        };
    }

    private function feedback(mixed $courseId): string
    {
        $course = Course::query()->find($courseId);

        return $course
            ? 'Pilihan tercatat: '.$course->name.'.'
            : 'Pilihan tercatat.';
    }
}
