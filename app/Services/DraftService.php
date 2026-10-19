<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Duel;
use App\Models\DuelDraft;
use App\Models\DuelRound;
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

    private array $plans = [];

    public function createDuel(Challenge $challenge): Duel
    {
        $pool = $this->drawPool($challenge->course_ids);

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
                'max_rounds' => $challenge->roundCount(),
                'draft_step' => 0,
                'turn_deadline' => now()->addSeconds(self::SECONDS),
            ]);

            DuelRound::create([
                'duel_id' => $duel->id,
                'round_number' => 1,
                'first_ban_user_id' => $first,
                'status' => DuelRound::STATUS_DRAFTING,
            ]);

            $this->startRound($duel);

            return $duel;
        });
    }

    // Mulai ronde, isi pick yang sudah pasti
    public function startRound(Duel $duel): void
    {
        $round = $this->ensureRound($duel);

        if ($round) {
            $this->fillForced($duel, $round);
        }
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

        if ((int) $duel->draft_step < $this->steps($duel) && $duel->turn_deadline === null) {
            $duel->forceFill(['turn_deadline' => now()->addSeconds(self::SECONDS)])->save();
        }

        return $round;
    }

    // Jumlah ban dan pick menyesuaikan sisa matkul
    public function roundPlan(Duel $duel, ?int $roundNumber = null): array
    {
        $roundNumber ??= (int) $duel->current_round;

        return $this->plans[$duel->id.':'.$roundNumber] ??= $this->buildPlan($duel, $roundNumber);
    }

    public function steps(Duel $duel): int
    {
        return $this->roundPlan($duel)['steps'];
    }

    public function orderForRound(DuelRound $round): array
    {
        $first = (int) $round->first_ban_user_id;
        $duel = $round->duel;
        $second = (int) ((int) $duel->player1_id === $first ? $duel->player2_id : $duel->player1_id);

        // Pick pertama milik yang ban kedua
        return match ($this->roundPlan($duel, $round->round_number)['steps']) {
            4 => [$first, $second, $second, $first],
            3 => [$first, $second, $first],
            2 => [$second, $first],
            default => [$first],
        };
    }

    public function stepAction(int $step, int $bans = self::BAN_PER_ROUND): string
    {
        return $step < $bans ? DuelDraft::ACTION_BAN : DuelDraft::ACTION_PICK;
    }

    public function actionAt(Duel $duel, int $step): string
    {
        return $this->stepAction($step, $this->roundPlan($duel)['bans']);
    }

    public function turnUserId(Duel $duel, DuelRound $round): ?int
    {
        $step = (int) $duel->draft_step;

        if ($step >= $this->steps($duel)) {
            return null;
        }

        return $this->orderForRound($round)[$step] ?? null;
    }

    public function availableCourses(Duel $duel): Collection
    {
        $candidates = $this->roundPlan($duel)['candidates'];

        if ($candidates === []) {
            return Course::query()->whereRaw('1 = 0')->get();
        }

        $usedNow = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->where('round_number', $duel->current_round)
            ->pluck('course_id');

        return Course::query()
            ->whereIn('id', $candidates)
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

            if ($step >= $this->steps($fresh)) {
                throw new DomainException('Draft ronde ini selesai.');
            }

            if ($this->turnUserId($fresh, $round) !== $userId) {
                throw new DomainException('Bukan giliranmu untuk memilih.');
            }

            if ($isAuto && $fresh->turn_deadline && now()->lt($fresh->turn_deadline)) {
                throw new DomainException('Waktu giliran belum habis.');
            }

            $action = $this->actionAt($fresh, $step);
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

            $draft = $this->record($fresh, $round, (int) $courseId, $isAuto ? DuelDraft::REASON_TIMEOUT : null);
            $this->fillForced($fresh, $round);

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

            if (! $round || (int) $fresh->draft_step >= $this->steps($fresh)) {
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

    // Simpan langkah lalu majukan giliran
    private function record(Duel $duel, DuelRound $round, int $courseId, ?string $autoReason = null): DuelDraft
    {
        $step = (int) $duel->draft_step;

        $draft = DuelDraft::create([
            'duel_id' => $duel->id,
            'round_number' => $duel->current_round,
            'step' => $step,
            'user_id' => $this->turnUserId($duel, $round),
            'course_id' => $courseId,
            'action' => $this->actionAt($duel, $step),
            'is_auto' => $autoReason !== null,
            'auto_reason' => $autoReason,
        ]);

        $finished = $step + 1 >= $this->steps($duel);

        $duel->forceFill([
            'draft_step' => $step + 1,
            'turn_deadline' => $finished ? null : now()->addSeconds(self::SECONDS),
            'status' => $finished ? Duel::STATUS_BATTLE : Duel::STATUS_DRAFTING,
            'started_at' => $finished ? ($duel->started_at ?? now()) : $duel->started_at,
        ])->save();

        // Hitung mundur sebelum soal pertama
        if ($finished) {
            $round->forceFill(['starts_at' => now()->addSeconds(BattleService::READY_SECONDS)])->save();
        }

        return $draft;
    }

    // Sisa pick tanpa pilihan diisi otomatis
    private function fillForced(Duel $duel, DuelRound $round): void
    {
        $plan = $this->roundPlan($duel);

        while ($duel->status === Duel::STATUS_DRAFTING && (int) $duel->draft_step < $plan['steps']) {
            $step = (int) $duel->draft_step;
            $available = $this->availableCourses($duel)->modelKeys();

            if ($this->stepAction($step, $plan['bans']) !== DuelDraft::ACTION_PICK || count($available) > $plan['steps'] - $step) {
                return;
            }

            $this->record($duel, $round, (int) $available[0], DuelDraft::REASON_FORCED);
        }
    }

    private function buildPlan(Duel $duel, int $roundNumber): array
    {
        $pool = $this->poolIds($duel);

        $pickedEarlier = DuelDraft::query()
            ->where('duel_id', $duel->id)
            ->where('round_number', '<', $roundNumber)
            ->where('action', DuelDraft::ACTION_PICK)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id);

        $candidates = $pool->diff($pickedEarlier)->values();

        // Stok habis, pool dipakai ulang
        if ($candidates->count() < min(self::PICK_PER_ROUND, $pool->count())) {
            $candidates = $pool;
        }

        $picks = min(self::PICK_PER_ROUND, $candidates->count());
        $bans = max(0, min(self::BAN_PER_ROUND, $candidates->count() - $picks));

        return [
            'candidates' => $candidates->all(),
            'bans' => $bans,
            'picks' => $picks,
            'steps' => $bans + $picks,
        ];
    }

    protected function drawPool(?array $courseIds = null): Collection
    {
        if (! empty($courseIds)) {
            $room = Course::query()
                ->eligible()
                ->whereIn('id', $courseIds)
                ->inRandomOrder()
                ->limit(self::POOL_SIZE)
                ->pluck('id');

            if ($room->isEmpty()) {
                throw new DomainException('Matkul di room ini belum punya cukup soal aktif. Minta lawanmu mengatur ulang room-nya.');
            }

            return $room->values();
        }

        $eligible = Course::query()
            ->eligible()
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
