<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuelRound extends Model
{
    public const STATUS_DRAFTING = 'drafting';
    public const STATUS_BATTLE = 'battle';
    public const STATUS_FINISHED = 'finished';
    public const STATUSES = [self::STATUS_DRAFTING, self::STATUS_BATTLE, self::STATUS_FINISHED];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'starts_at' => 'datetime',
        ];
    }

    public function duel(): BelongsTo
    {
        return $this->belongsTo(Duel::class);
    }

    public function firstBanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_ban_user_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function roundQuestions(): HasMany
    {
        return $this->hasMany(DuelRoundQuestion::class)->orderBy('position');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'duel_round_questions')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DuelAnswer::class);
    }
}
