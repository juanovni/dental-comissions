<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'internal_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'internal_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }
}
