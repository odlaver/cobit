<?php

namespace App\Http\Controllers;

use App\Models\Duel;
use App\Models\DuelAnswer;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Services\ScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DuelController extends Controller
{
    public function __construct(private ScoringService $scoring)
    {
    }

    public function show(Request $request, Duel $duel): RedirectResponse
    {
        abort_unless($duel->hasPlayer($request->user()), 403);

        return match (true) {
            $duel->status === Duel::STATUS_BATTLE => redirect()->route('duels.battle', $duel),
            $duel->status === Duel::STATUS_DRAFTING && Route::has('duels.draft') => redirect()->route('duels.draft', $duel),
            default => redirect()->route('duels.result', $duel),
        };
    }

    public function result(Request $request, Duel $duel): View
    {
        $user = $request->user();
        abort_unless($duel->hasPlayer($user), 403);

        $opponent = $duel->opponentOf($user);
        $rounds = $duel->rounds()
            ->where('status', DuelRound::STATUS_FINISHED)
            ->with(['roundQuestions.question.material.course', 'answers'])
            ->get();

        $picks = DuelDraft::where('duel_id', $duel->id)
            ->where('action', DuelDraft::ACTION_PICK)
            ->with(['course', 'user'])
            ->orderBy('step')
            ->get()
            ->groupBy('round_number');

        $summary = $rounds->map(fn (DuelRound $round) => [
            'round' => $round,
            'picks' => $picks[$round->round_number] ?? collect(),
            'me' => $this->scoring->roundStats($round, $user->id),
            'opponent' => $this->scoring->roundStats($round, $opponent->id),
            'myCells' => $this->cells($round, $user->id),
            'opponentCells' => $this->cells($round, $opponent->id),
            'mistakes' => $this->mistakes($round, $user->id),
        ]);

        return view('duels.result', [
            'duel' => $duel,
            'user' => $user->fresh(),
            'opponent' => $opponent,
            'outcome' => $this->outcome($duel, $user->id),
            'summary' => $summary,
            'roundWins' => [
                $rounds->where('winner_id', $user->id)->count(),
                $rounds->where('winner_id', $opponent->id)->count(),
            ],
            'mastery' => $this->mastery($rounds, $user->id, $opponent->id),
            'canRematch' => Route::has('challenges.store'),
        ]);
    }

    private function outcome(Duel $duel, int $userId): string
    {
        if ($duel->status !== Duel::STATUS_FINISHED) {
            return 'ongoing';
        }

        return match (true) {
            $duel->winner_id === null => 'draw',
            (int) $duel->winner_id === $userId => 'win',
            default => 'lose',
        };
    }

    private function cells(DuelRound $round, int $userId): array
    {
        return $round->roundQuestions->map(function ($item) use ($round, $userId) {
            $answer = $round->answers->first(fn (DuelAnswer $a) => (int) $a->user_id === $userId && (int) $a->question_id === (int) $item->question_id);

            return match (true) {
                $answer?->is_correct === true => 'correct',
                $answer?->selected_answer !== null => 'wrong',
                default => 'skipped',
            };
        })->all();
    }

    private function mistakes(DuelRound $round, int $userId): array
    {
        return $round->roundQuestions->map(function ($item) use ($round, $userId) {
            $answer = $round->answers->first(fn (DuelAnswer $a) => (int) $a->user_id === $userId && (int) $a->question_id === (int) $item->question_id);

            if ($answer?->is_correct) {
                return null;
            }

            return ['question' => $item->question, 'selected' => $answer?->selected_answer];
        })->filter()->values()->all();
    }

    private function mastery($rounds, int $me, int $opponent): array
    {
        $rows = [];

        foreach ($rounds as $round) {
            foreach ($round->roundQuestions as $item) {
                $course = $item->question->material->course->name;
                $rows[$course] ??= ['total' => 0, 'me' => 0, 'opponent' => 0];
                $rows[$course]['total']++;

                foreach (['me' => $me, 'opponent' => $opponent] as $key => $id) {
                    $correct = $round->answers->contains(fn (DuelAnswer $a) => (int) $a->user_id === $id
                        && (int) $a->question_id === (int) $item->question_id
                        && $a->is_correct);
                    $rows[$course][$key] += (int) $correct;
                }
            }
        }

        return $rows;
    }
}
