<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuelDraft extends Model
{
    public const ACTION_BAN = 'ban';
    public const ACTION_PICK = 'pick';
    public const ACTIONS = [self::ACTION_BAN, self::ACTION_PICK];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'step' => 'integer',
            'is_auto' => 'boolean',
        ];
    }

    public function duel(): BelongsTo
    {
        return $this->belongsTo(Duel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
