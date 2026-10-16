<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Duel extends Model
{
    public const STATUS_DRAFTING = 'drafting';
    public const STATUS_BATTLE = 'battle';
    public const STATUS_FINISHED = 'finished';
    public const STATUSES = [self::STATUS_DRAFTING, self::STATUS_BATTLE, self::STATUS_FINISHED];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pool' => 'array',
            'current_round' => 'integer',
            'draft_step' => 'integer',
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
}
