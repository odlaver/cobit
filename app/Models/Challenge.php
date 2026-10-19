<?php

namespace App\Models;

use App\Services\DraftService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Challenge extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_DECLINED, self::STATUS_CANCELLED, self::STATUS_EXPIRED];

    public const EXPIRE_MINUTES = 10;
    public const ROUND_OPTIONS = [1, 3];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'course_ids' => 'array',
            'rounds' => 'integer',
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

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInvolving(Builder $query, int ...$userIds): void
    {
        $query->where(fn (Builder $inner) => $inner->whereIn('challenger_id', $userIds)->orWhereIn('opponent_id', $userIds));
    }

    // Tantangan lama otomatis kedaluwarsa
    public static function expireStale(?int $userId = null): int
    {
        return static::query()
            ->pending()
            ->where('created_at', '<', now()->subMinutes(self::EXPIRE_MINUTES))
            ->when($userId, fn (Builder $query) => $query->involving($userId))
            ->update(['status' => self::STATUS_EXPIRED, 'responded_at' => now()]);
    }

    // Matkul room untuk banyak tantangan sekaligus
    public static function roomCourses(iterable $challenges): Collection
    {
        $ids = collect($challenges)->pluck('course_ids')->flatten()->filter()->unique();

        return $ids->isEmpty()
            ? collect()
            : Course::query()->whereIn('id', $ids)->get(['id', 'code', 'name'])->keyBy('id');
    }

    public function roundCount(): int
    {
        return $this->rounds ?: Duel::DEFAULT_ROUNDS;
    }

    // Ringkasan isi room
    public function roomLabel(?Collection $courses = null): string
    {
        $rounds = $this->roundCount() === 1 ? '1 ronde' : 'Best of '.$this->roundCount();

        if (empty($this->course_ids)) {
            return DraftService::POOL_SIZE.' matkul acak · '.$rounds;
        }

        $courses ??= static::roomCourses([$this]);
        $names = collect($this->course_ids)->map(fn ($id) => $courses->get($id)?->name)->filter()->values();
        $extra = $names->count() > 2 ? ' +'.($names->count() - 2) : '';

        return $names->take(2)->join(', ').$extra.' · '.$rounds;
    }
}
