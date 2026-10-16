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
    public const ROLE_MAHASISWA = 'mahasiswa';
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_MAHASISWA];

    public const TIERS = ['God' => 600, 'Hacker' => 300, 'Pro' => 100, 'Noob' => 0];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'angkatan' => 'integer',
            'points' => 'integer',
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

    public function scopeMahasiswa(Builder $query): void
    {
        $query->where('role', self::ROLE_MAHASISWA);
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
