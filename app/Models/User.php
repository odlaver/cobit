<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'npm', 'email', 'password', 'prodi', 'angkatan', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_ASDOS = 'asdos';
    public const ROLE_MAHASISWA = 'mahasiswa';
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_ASDOS, self::ROLE_MAHASISWA];

    public const TIERS = ['God' => 600, 'Hacker' => 300, 'Pro' => 100, 'Noob' => 0];

    public const PRESENCE_ONLINE = 'online';
    public const PRESENCE_DUELING = 'dueling';
    public const PRESENCE_OFFLINE = 'offline';
    private const SEEN_EVERY_SECONDS = 30;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'angkatan' => 'integer',
            'points' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public static function tierFor(int $points): string
    {
        foreach (self::TIERS as $tier => $min) {
            if ($points >= $min) {
                return $tier;
            }
        }

        return 'Noob';
    }

    protected function tier(): Attribute
    {
        return Attribute::get(fn () => self::tierFor($this->points ?? 0));
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar ? asset('storage/'.$this->avatar) : null);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAsdos(): bool
    {
        return $this->role === self::ROLE_ASDOS;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_ASDOS => 'admin.courses.index',
            default => 'dashboard',
        };
    }

    public function scopeMahasiswa(Builder $query): void
    {
        $query->where('role', self::ROLE_MAHASISWA);
    }

    public function scopeOnline(Builder $query): void
    {
        $query->where('last_seen_at', '>=', now()->subMinutes(config('cobit.online_minutes')));
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at?->gte(now()->subMinutes(config('cobit.online_minutes'))) ?? false;
    }

    // Online, sedang berduel, atau offline
    public function presence(?bool $dueling = null): string
    {
        if (! $this->isOnline()) {
            return self::PRESENCE_OFFLINE;
        }

        $dueling ??= $this->duels()->active()->exists();

        return $dueling ? self::PRESENCE_DUELING : self::PRESENCE_ONLINE;
    }

    // Catat aktivitas tanpa menyentuh updated_at
    public function touchLastSeen(): void
    {
        $now = now();

        if ($this->last_seen_at?->gt($now->copy()->subSeconds(self::SEEN_EVERY_SECONDS))) {
            return;
        }

        static::query()->whereKey($this->id)->toBase()->update(['last_seen_at' => $now]);
        $this->forceFill(['last_seen_at' => $now])->syncOriginalAttribute('last_seen_at');
    }

    public function clearLastSeen(): void
    {
        static::query()->whereKey($this->id)->toBase()->update(['last_seen_at' => null]);
        $this->forceFill(['last_seen_at' => null])->syncOriginalAttribute('last_seen_at');
    }

    public function sentChallenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'challenger_id');
    }

    public function receivedChallenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'opponent_id');
    }

    public function duels(): Builder
    {
        return Duel::where(fn ($q) => $q->where('player1_id', $this->id)->orWhere('player2_id', $this->id));
    }
}
