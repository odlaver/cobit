<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Duel;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Models\Question;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DraftService
{
    public const SECONDS = 30;
    public const POOL_SIZE = 10;
    public const MIN_QUESTIONS = 10;
    public const BAN_PER_ROUND = 2;
    public const PICK_PER_ROUND = 2;
    public const STEPS = self::BAN_PER_ROUND + self::PICK_PER_ROUND;
    public const MAX_ROUNDS = 3;

    public function createDuel(Challenge $challenge): Duel
    {
        $pool = $this->drawPool();

        return DB::transaction(function () use ($challenge, $pool) {
            $first = (int) $challenge->challenger_id;
            $second = (int) $challenge->opponent_id;

            if (random_int(0, 1) === 1) {
                [$first, $second] = [$second, $first];
            }

            $duel = Duel::create([
                'challenge_id' => $challenge->id,
                'player1_id' => $first,
                'player2_id' => $second,
                'pool' => $pool->all(),
                'status' => Duel::STATUS_DRAFTING,
                'current_round' => 1,
                'draft_step' => 0,
                'turn_deadline' => now()->addSeconds(self::SECONDS),
            ]);

            DuelRound::create([
                'duel_id' => $duel->id,
                'round_number' => 1,
                'first_ban_user_id' => $first,
                'status' => DuelRound::STATUS_DRAFTING,
            ]);

            return $duel;
        });
    }

    public function ensureRound(Duel $duel): ?DuelRound
    {
        if ($duel->status !== Duel::STATUS_DRAFTING) {
            return null;
        }

        $round = DuelRound::firstOrCreate([
            'duel_id' => $duel->id,
            'round_number' => $duel->current_round,
        ], [
            'status' => DuelRound::STATUS_DRAFTING,
        ]);

        if ($round->first_ban_user_id === null) {
            $round->forceFill([
                'first_ban_user_id' => $this->firstBannerFor($duel, $round->round_number),
            ])->save();
        }

        if ((int) $duel->draft_step < self::STEPS && $duel->turn_deadline === null) {
            $duel->forceFill(['turn_deadline' => now()->addSeconds(self::SECONDS)])->save();
        }

        return $round;
    }

    public function orderForRound(DuelRound $round): array
    {
        $first = (int) $round->first_ban_user_id;
        $duel = $round->duel;
        $second = (int) ($duel->player1_id === $first ? $duel->player2_id : $duel->player1_id);

        return [$first, $second, $second, $first];
    }

    public function stepAction(int $step): string
    {
        return $step < self::BAN_PER_ROUND ? DuelDraft::ACTION_BAN : DuelDraft::ACTION_PICK;
    }

    public function turnUserId(Duel $duel, DuelRound $round): ?int
    {
        $step = (int) $duel->draft_step;

        if ($step >= self::STEPS) {
            return null;
        }

        return $this->orderForRound($round)[$step] ?? null;
    }

    public function availableCourses(Duel $duel): Collection
    {
        $pool = $this->poolIds($duel);

        if ($pool->isEmpty()) {
            return Course::query()->whereRaw('1 = 0')->get();
        }

        $pickedEarlier = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->where('round_number', '<', $duel->current_round)
            ->where('action', DuelDraft::ACTION_PICK)
            ->pluck('course_id');

        $usedNow = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->where('round_number', $duel->current_round)
            ->pluck('course_id');

        return Course::query()
            ->whereIn('id', $pool)
            ->whereNotIn('id', $pickedEarlier)
            ->whereNotIn('id', $usedNow)
            ->orderBy('semester')
            ->orderBy('name')
            ->get();
    }

    public function poolState(Duel $duel): Collection
    {
        $pool = $this->poolIds($duel);

        if ($pool->isEmpty()) {
            return collect();
        }

        $availableIds = $this->availableCourses($duel)->modelKeys();

        $drafts = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->with('user:id,name')
            ->orderBy('round_number')
            ->orderBy('step')
            ->get();

        $picked = $drafts->where('action', DuelDraft::ACTION_PICK)->keyBy('course_id');
        $bans = $drafts->where('action', DuelDraft::ACTION_BAN)->groupBy('course_id');
        $last = $drafts->groupBy('course_id')->map(fn (Collection $rows) => $rows->last());

        return Course::query()
            ->whereIn('id', $pool)
            ->orderBy('semester')
            ->orderBy('name')
            ->get()
            ->map(function (Course $course) use ($picked, $bans, $last, $availableIds) {
                $pick = $picked->get($course->id);
                $ban = $bans->get($course->id) ?? collect();
                $recent = $last->get($course->id);

                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'semester' => $course->semester,
                    'available' => in_array($course->id, $availableIds, false),
                    'picked_round' => $pick?->round_number,
                    'picked_by' => $pick?->user?->name,
                    'banned_rounds' => $ban->pluck('round_number')->unique()->values()->all(),
                    'last_action' => $recent?->action,
                    'last_round' => $recent?->round_number,
                    'last_user_name' => $recent?->user?->name,
                ];
            })
            ->values();
    }

    public function act(Duel $duel, int $userId, ?int $courseId = null, bool $isAuto = false): DuelDraft
    {
        return DB::transaction(function () use ($duel, $userId, $courseId, $isAuto) {
            $fresh = Duel::query()->whereKey($duel->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== Duel::STATUS_DRAFTING) {
                throw new DomainException('Duel ini sudah tidak di tahap draft.');
            }

            $round = $this->ensureRound($fresh);

            if (! $round) {
                throw new DomainException('Duel ini sudah tidak di tahap draft.');
            }

            $step = (int) $fresh->draft_step;

            if ($step >= self::STEPS) {
                throw new DomainException('Draft ronde ini selesai.');
            }

            if ($this->turnUserId($fresh, $round) !== $userId) {
                throw new DomainException('Bukan giliranmu untuk memilih.');
            }

            if ($isAuto && $fresh->turn_deadline && now()->lt($fresh->turn_deadline)) {
                throw new DomainException('Waktu giliran belum habis.');
            }

            $action = $this->stepAction($step);
            $available = $this->availableCourses($fresh)->modelKeys();

            if ($courseId === null) {
                if ($available === []) {
                    throw new DomainException('Tidak ada matkul yang tersedia untuk dipilih.');
                }
                $courseId = (int) $available[array_rand($available)];
            } elseif (! in_array((int) $courseId, $available, false)) {
                throw new DomainException(
                    $action === DuelDraft::ACTION_BAN
                        ? 'Matkul itu sudah tidak tersedia untuk dibanned.'
                        : 'Matkul itu sudah tidak tersedia untuk dipilih.'
                );
            }

            $draft = DuelDraft::create([
                'duel_id' => $fresh->id,
                'round_number' => $fresh->current_round,
                'step' => $step,
                'user_id' => $userId,
                'course_id' => $courseId,
                'action' => $action,
                'is_auto' => $isAuto,
            ]);

            $nextStep = $step + 1;
            $finished = $nextStep >= self::STEPS;

            $fresh->forceFill([
                'draft_step' => $nextStep,
                'turn_deadline' => $finished ? null : now()->addSeconds(self::SECONDS),
                'status' => $finished ? Duel::STATUS_BATTLE : Duel::STATUS_DRAFTING,
                'started_at' => $finished ? ($fresh->started_at ?? now()) : $fresh->started_at,
            ])->save();

            return $draft;
        });
    }

    public function tick(Duel $duel): void
    {
        for ($i = 0; $i <= self::STEPS; $i++) {
            $fresh = Duel::query()->find($duel->id);

            if (! $fresh || $fresh->status !== Duel::STATUS_DRAFTING) {
                return;
            }

            $round = $this->ensureRound($fresh);

            if (! $round || (int) $fresh->draft_step >= self::STEPS) {
                return;
            }

            if ($fresh->turn_deadline === null) {
                $fresh->forceFill(['turn_deadline' => now()->addSeconds(self::SECONDS)])->save();
                $fresh->refresh();
            }

            if (now()->lt($fresh->turn_deadline)) {
                return;
            }

            $userId = $this->turnUserId($fresh, $round);

            if ($userId === null) {
                return;
            }

            try {
                $this->act($fresh, $userId, null, true);
            } catch (DomainException) {
                return;
            }
        }
    }

    protected function drawPool(): Collection
    {
        $eligible = Course::query()
            ->where('is_active', true)
            ->whereHas('questions', fn ($query) => $query->where('status', Question::STATUS_ACTIVE), '>=', self::MIN_QUESTIONS)
            ->inRandomOrder()
            ->limit(self::POOL_SIZE)
            ->pluck('id');

        if ($eligible->count() < self::POOL_SIZE) {
            throw new DomainException(
                'Belum ada '.self::POOL_SIZE.' matkul dengan minimal '.self::MIN_QUESTIONS.' soal aktif. Tambahkan bank soal dulu sebelum menerima tantangan.'
            );
        }

        return $eligible->values();
    }

    protected function firstBannerFor(Duel $duel, int $roundNumber): int
    {
        if ($roundNumber <= 1) {
            return (int) $duel->player1_id;
        }

        if ($roundNumber === 2) {
            return (int) $duel->player2_id;
        }

        $previous = DuelRound::query()
            ->where('duel_id', $duel->id)
            ->where('round_number', $roundNumber - 1)
            ->value('winner_id');

        return $previous !== null ? (int) $previous : (int) $duel->player1_id;
    }

    protected function poolIds(Duel $duel): Collection
    {
        $pool = $duel->pool;

        if (! is_array($pool)) {
            return collect();
        }

        return collect($pool)->map(fn ($id) => (int) $id)->values();
    }
}
