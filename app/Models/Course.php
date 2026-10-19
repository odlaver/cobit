<?php

namespace App\Models;

use App\Services\DraftService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, Material::class);
    }

    // Aktif dan cukup soal untuk duel
    public function scopeEligible(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('questions', fn (Builder $inner) => $inner->where('status', Question::STATUS_ACTIVE), '>=', DraftService::MIN_QUESTIONS);
    }
}
