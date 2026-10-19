<?php

namespace App\Http\Controllers;

use App\Models\Duel;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Models\Question;
use App\Services\BattleService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BattleController extends Controller
{
    public function __construct(private BattleService $battle)
    {
    }

    public function show(Request $request, Duel $duel): View|RedirectResponse
    {
        abort_unless($duel->hasPlayer($request->user()), 403);

        if ($duel->status !== Duel::STATUS_BATTLE) {
            return redirect()->route('duels.show', $duel);
        }

        try {
            $round = $this->battle->openRound($duel);
        } catch (DomainException $e) {
            return redirect()->route('duels.result', $duel)->with('error', $e->getMessage());
        }

        $user = $request->user();
        $this->battle->expireStale($round, $user->id);
        $this->battle->settle($duel);

        if ($duel->fresh()->status !== Duel::STATUS_BATTLE || $round->fresh()->status === DuelRound::STATUS_FINISHED) {
            return redirect()->route('duels.show', $duel);
        }

        $opponent = $duel->opponentOf($user);
        $data = [
            'duel' => $duel,
            'round' => $round,
            'opponent' => $opponent,
            'total' => $round->roundQuestions()->count(),
            'myProgress' => $this->battle->progress($round, $user->id),
            'opponentProgress' => $this->battle->progress($round, $opponent->id),
            'roundWins' => $this->roundWins($duel, $user->id, $opponent->id),
            'courses' => DuelDraft::where('duel_id', $duel->id)
                ->where('round_number', $round->round_number)
                ->where('action', DuelDraft::ACTION_PICK)
                ->orderBy('step')
                ->with('course')
                ->get()
                ->pluck('course.name'),
        ];

        // Soal belum disajikan selama hitung mundur
        if (! $this->battle->isReady($round)) {
            return view('duels.ready', $data + [
                'startsIn' => max(0, now()->diffInMilliseconds($round->starts_at) / 1000),
                'lastRound' => $duel->rounds()->where('round_number', $round->round_number - 1)->first(),
            ]);
        }

        $next = $this->battle->nextQuestion($round, $user->id);

        if (! $next) {
            return view('duels.waiting', $data);
        }

        $answer = $this->battle->serve($round, $user->id, $next->question_id);

        return view('duels.battle', $data + [
            'item' => $next,
            'remaining' => $this->battle->remainingSeconds($answer),
            'limit' => BattleService::SECONDS,
        ]);
    }

    public function answer(Request $request, Duel $duel): RedirectResponse
    {
        abort_unless($duel->hasPlayer($request->user()), 403);

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer' => ['nullable', Rule::in(Question::ANSWERS)],
        ]);

        $round = $this->battle->currentRound($duel);

        if ($duel->status === Duel::STATUS_BATTLE && $round) {
            $this->battle->answer($duel, $round, $request->user()->id, $data['question_id'], $data['answer'] ?? null);
        }

        return redirect()->route('duels.battle', $duel);
    }

    public function state(Request $request, Duel $duel): JsonResponse
    {
        abort_unless($duel->hasPlayer($request->user()), 403);

        $this->battle->settle($duel);
        $duel->refresh();
        $round = $this->battle->currentRound($duel);
        $roundNumber = (int) $request->query('round', $duel->current_round);

        if ($duel->status !== Duel::STATUS_BATTLE || ! $round || $roundNumber !== $duel->current_round) {
            return response()->json(['done' => true, 'redirect' => route('duels.show', $duel)]);
        }

        return response()->json([
            'done' => false,
            'total' => $round->roundQuestions()->count(),
            'opponent' => $this->battle->progress($round, $duel->opponentOf($request->user())->id),
        ]);
    }

    private function roundWins(Duel $duel, int $me, int $opponent): array
    {
        $wins = $duel->rounds()->whereNotNull('winner_id')->pluck('winner_id')->countBy();

        return [$wins[$me] ?? 0, $wins[$opponent] ?? 0];
    }
}
