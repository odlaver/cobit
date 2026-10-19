<?php

namespace App\Services;

use App\Models\Duel;
use App\Models\DuelAnswer;
use App\Models\DuelRound;
use App\Models\User;

class ScoringService
{
    public const WIN_POINTS = 30;
    public const DRAW_POINTS = 10;
    public const LOSS_POINTS = 15;
    public const UPSET_BONUS = 20;

    public function roundStats(DuelRound $round, int $userId): array
    {
        $total = $round->roundQuestions()->count();
        $answers = $round->answers()->where('user_id', $userId)->whereNotNull('answered_at')->get();
        $limitMs = BattleService::SECONDS * 1000;

        return [
            'correct' => $answers->where('is_correct', true)->count(),
            'answered' => $answers->count(),
            'total' => $total,
            'time_ms' => $answers->sum(fn (DuelAnswer $answer) => $this->elapsedMs($answer))
                + ($total - $answers->count()) * $limitMs,
        ];
    }

    public function elapsedMs(DuelAnswer $answer): int
    {
        $ms = $answer->served_at->diffInMilliseconds($answer->answered_at);

        return (int) min(BattleService::SECONDS * 1000, max(0, $ms));
    }

    public function roundWinner(DuelRound $round, Duel $duel): ?int
    {
        [$p1, $p2] = $duel->playerIds();
        $a = $this->roundStats($round, $p1);
        $b = $this->roundStats($round, $p2);

        return $this->pick($p1, $p2, [$a['correct'], -$a['time_ms']], [$b['correct'], -$b['time_ms']]);
    }

    public function finishDuel(Duel $duel): void
    {
        [$p1, $p2] = $duel->playerIds();
        $totals = [$p1 => [0, 0, 0], $p2 => [0, 0, 0]];

        foreach ($duel->rounds()->get() as $round) {
            foreach ([$p1, $p2] as $id) {
                $stats = $this->roundStats($round, $id);
                $totals[$id][0] += (int) ((int) $round->winner_id === $id);
                $totals[$id][1] += $stats['correct'];
                $totals[$id][2] -= $stats['time_ms'];
            }
        }

        // Ronde menang, lalu benar, lalu waktu
        $winnerId = $this->pick($p1, $p2, $totals[$p1], $totals[$p2]);

        $duel->update([
            'status' => Duel::STATUS_FINISHED,
            'winner_id' => $winnerId,
            'finished_at' => now(),
        ]);

        $this->applyPoints($duel, $winnerId);
    }

    private function pick(int $p1, int $p2, array $a, array $b): ?int
    {
        return match ($a <=> $b) {
            1 => $p1,
            -1 => $p2,
            default => null,
        };
    }

    private function applyPoints(Duel $duel, ?int $winnerId): void
    {
        $players = User::whereIn('id', $duel->playerIds())->lockForUpdate()->get()->keyBy('id');
        $loser = $winnerId === null ? null : $players->first(fn (User $user) => $user->id !== $winnerId);
        $upset = $loser && $players[$winnerId]->tier === 'Noob' && $loser->tier === 'God';

        // Poin kalah tidak di bawah nol
        $deltas = $players->map(fn (User $user) => match (true) {
            $winnerId === null => self::DRAW_POINTS,
            $user->id === $winnerId => self::WIN_POINTS + ($upset ? self::UPSET_BONUS : 0),
            default => -min(self::LOSS_POINTS, $user->points),
        });

        $players->each(fn (User $user) => $user->forceFill(['points' => $user->points + $deltas[$user->id]])->save());

        $duel->update([
            'player1_delta' => $deltas[(int) $duel->player1_id],
            'player2_delta' => $deltas[(int) $duel->player2_id],
        ]);
    }
}
