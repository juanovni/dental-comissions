<?php

namespace App\Models;

use App\Enums\LocalLanguagePatternType;
use App\Services\LocalLanguagePatternService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalLanguagePattern extends Model
{
    protected $fillable = [
        'type',
        'phrase',
        'normalized_phrase',
        'value',
        'locale',
        'is_active',
        'source',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocalLanguagePatternType::class,
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $pattern): void {
            if ($pattern->phrase) {
                $pattern->normalized_phrase = app(LocalLanguagePatternService::class)->normalize($pattern->phrase);
            }
        });

        static::saved(fn (): mixed => app(LocalLanguagePatternService::class)->clearCache());
        static::deleted(fn (): mixed => app(LocalLanguagePatternService::class)->clearCache());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, LocalLanguagePatternType|string $type): Builder
    {
        return $query->where('type', $type instanceof LocalLanguagePatternType ? $type->value : $type);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
