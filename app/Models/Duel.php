<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Duel extends Model
{
    public const STATUS_DRAFTING = 'drafting';
    public const STATUS_BATTLE = 'battle';
    public const STATUS_FINISHED = 'finished';
    public const STATUSES = [self::STATUS_DRAFTING, self::STATUS_BATTLE, self::STATUS_FINISHED];
    public const DEFAULT_ROUNDS = 3;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pool' => 'array',
            'current_round' => 'integer',
            'max_rounds' => 'integer',
            'draft_step' => 'integer',
            'player1_delta' => 'integer',
            'player2_delta' => 'integer',
            'turn_deadline' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(DuelDraft::class)->orderBy('round_number')->orderBy('step');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(DuelRound::class)->orderBy('round_number');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_FINISHED);
    }

    // Pemain yang sedang punya duel aktif
    public static function busyPlayerIds(): array
    {
        return static::query()
            ->active()
            ->get(['player1_id', 'player2_id'])
            ->flatMap(fn (Duel $duel) => [(int) $duel->player1_id, (int) $duel->player2_id])
            ->unique()
            ->values()
            ->all();
    }

    // Menang mayoritas ronde
    public function winsNeeded(): int
    {
        return intdiv($this->max_rounds ?: self::DEFAULT_ROUNDS, 2) + 1;
    }

    public function deltaFor(User $user): ?int
    {
        return $user->id === (int) $this->player1_id ? $this->player1_delta : $this->player2_delta;
    }

    public function hasPlayer(User $user): bool
    {
        return in_array($user->id, $this->playerIds(), true);
    }

    public function opponentOf(User $user): User
    {
        return $user->id === (int) $this->player1_id ? $this->player2 : $this->player1;
    }

    public function playerIds(): array
    {
        return [(int) $this->player1_id, (int) $this->player2_id];
    }
}
