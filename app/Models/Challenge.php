<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Challenge extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_DECLINED, self::STATUS_CANCELLED];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function duel(): HasOne
    {
        return $this->hasOne(Duel::class);
    }
}
