<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    public const SOURCES = ['manual', 'ai'];

    public const DIFFICULTIES = ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'];

    public const ANSWERS = ['a', 'b', 'c', 'd'];

    protected $guarded = ['id'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
