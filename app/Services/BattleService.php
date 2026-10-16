<?php

namespace App\Services;

use App\Models\Duel;
use App\Models\DuelAnswer;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Models\DuelRoundQuestion;
use App\Models\Question;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BattleService
{
    public const SECONDS = 20;
    public const PER_COURSE = 5;
    public const MAX_ROUNDS = 3;
    private const GRACE_SECONDS = 3;
    private const ROUND_BUFFER_SECONDS = 60;

    public function __construct(private ScoringService $scoring)
    {
    }

    public function openRound(Duel $duel): DuelRound
    {
        return DB::transaction(function () use ($duel) {
            Duel::whereKey($duel->id)->lockForUpdate()->first();

            $round = DuelRound::firstOrCreate([
                'duel_id' => $duel->id,
                'round_number' => $duel->current_round,
            ]);

            if (! $round->roundQuestions()->exists()) {
                $this->fillQuestions($round);
            }

            if ($round->status === DuelRound::STATUS_DRAFTING) {
                $round->update(['status' => DuelRound::STATUS_BATTLE]);
            }

            return $round;
        });
    }

    public function currentRound(Duel $duel): ?DuelRound
    {
        return $duel->rounds()->where('round_number', $duel->current_round)->first();
    }

    public function nextQuestion(DuelRound $round, int $userId): ?DuelRoundQuestion
    {
        $answered = $round->answers()->where('user_id', $userId)->whereNotNull('answered_at')->pluck('question_id');

        return $round->roundQuestions()
            ->whereNotIn('question_id', $answered)
            ->with('question.material.course')
            ->first();
    }

    public function serve(DuelRound $round, int $userId, int $questionId): DuelAnswer
    {
        return DuelAnswer::createOrFirst(
            ['duel_round_id' => $round->id, 'user_id' => $userId, 'question_id' => $questionId],
            ['served_at' => now()],
        );
    }

    public function remainingSeconds(DuelAnswer $answer): float
    {
        return max(0, self::SECONDS - $answer->served_at->diffInMilliseconds(now()) / 1000);
    }

    public function answer(Duel $duel, DuelRound $round, int $userId, int $questionId, ?string $choice): void
    {
        DB::transaction(function () use ($round, $userId, $questionId, $choice) {
            $answer = DuelAnswer::where([
                'duel_round_id' => $round->id,
                'user_id' => $userId,
                'question_id' => $questionId,
            ])->whereNull('answered_at')->lockForUpdate()->first();

            if (! $answer) {
                return;
            }

            $late = $answer->served_at->diffInMilliseconds(now()) > (self::SECONDS + self::GRACE_SECONDS) * 1000;
            $choice = $late ? null : $choice;

            $answer->update([
                'selected_answer' => $choice,
                'is_correct' => $choice !== null && $choice === $answer->question->correct_answer,
                'answered_at' => $late ? $answer->served_at->copy()->addSeconds(self::SECONDS) : now(),
            ]);
        });

        $this->settle($duel);
    }

    // Soal yang ditinggal dianggap habis waktu
    public function expireStale(DuelRound $round, int $userId): void
    {
        $round->answers()
            ->where('user_id', $userId)
            ->whereNull('answered_at')
            ->where('served_at', '<', now()->subSeconds(self::SECONDS + self::GRACE_SECONDS))
            ->get()
            ->each(fn (DuelAnswer $answer) => $answer->update([
                'answered_at' => $answer->served_at->copy()->addSeconds(self::SECONDS),
            ]));
    }

    public function progress(DuelRound $round, int $userId): int
    {
        return $round->answers()->where('user_id', $userId)->whereNotNull('answered_at')->count();
    }

    public function settle(Duel $duel): void
    {
        DB::transaction(function () use ($duel) {
            $duel = Duel::whereKey($duel->id)->lockForUpdate()->first();

            if ($duel->status !== Duel::STATUS_BATTLE) {
                return;
            }

            $round = $this->currentRound($duel);
            $total = $round?->roundQuestions()->count() ?? 0;

            if ($total === 0 || $round->status === DuelRound::STATUS_FINISHED) {
                return;
            }

            $allDone = collect($duel->playerIds())->every(fn (int $id) => $this->progress($round, $id) >= $total);

            if (! $allDone && now()->lt($this->deadline($round, $total))) {
                return;
            }

            $round->update([
                'status' => DuelRound::STATUS_FINISHED,
                'winner_id' => $this->scoring->roundWinner($round, $duel),
            ]);

            $this->advance($duel);
        });
    }

    private function advance(Duel $duel): void
    {
        $topWins = $duel->rounds()->whereNotNull('winner_id')->pluck('winner_id')->countBy()->max() ?? 0;

        if ($topWins >= 2 || $duel->current_round >= self::MAX_ROUNDS) {
            $this->scoring->finishDuel($duel);

            return;
        }

        // Kembali ke draft ronde berikutnya
        $duel->update([
            'status' => Duel::STATUS_DRAFTING,
            'current_round' => $duel->current_round + 1,
            'draft_step' => 0,
            'turn_deadline' => null,
        ]);
    }

    // Batas ronde untuk pemain yang kabur
    private function deadline(DuelRound $round, int $total): Carbon
    {
        $start = Carbon::parse($round->roundQuestions()->min('created_at'));

        return $start->addSeconds($total * self::SECONDS + self::ROUND_BUFFER_SECONDS);
    }

    private function fillQuestions(DuelRound $round): void
    {
        $courseIds = DuelDraft::where('duel_id', $round->duel_id)
            ->where('round_number', $round->round_number)
            ->where('action', DuelDraft::ACTION_PICK)
            ->orderBy('step')
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            throw new DomainException('Draft ronde ini belum selesai.');
        }

        $picked = collect();

        foreach ($courseIds as $courseId) {
            $picked = $picked->merge($this->randomQuestions([$courseId], self::PER_COURSE, $picked));
        }

        // Tambal kalau stok soal kurang
        $target = self::PER_COURSE * $courseIds->count();
        if ($picked->count() < $target) {
            $picked = $picked->merge($this->randomQuestions($courseIds->all(), $target - $picked->count(), $picked));
        }

        if ($picked->isEmpty()) {
            throw new DomainException('Soal aktif untuk matkul ronde ini belum tersedia.');
        }

        $picked->shuffle()->values()->each(fn (int $id, int $i) => $round->roundQuestions()->create([
            'question_id' => $id,
            'position' => $i + 1,
        ]));
    }

    private function randomQuestions(array $courseIds, int $limit, Collection $except): Collection
    {
        return Question::query()
            ->where('status', Question::STATUS_ACTIVE)
            ->whereHas('material', fn (Builder $query) => $query->whereIn('course_id', $courseIds))
            ->whereNotIn('id', $except)
            ->inRandomOrder()
            ->limit($limit)
            ->pluck('id');
    }
}
