<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'professional_id',
        'procedure_id',
        'role',
        'commission_type',
        'fixed_amount',
        'percentage_value',
        'internal_rate',
        'starts_at',
        'ends_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProfessionalRole::class,
            'commission_type' => CommissionType::class,
            'fixed_amount' => 'decimal:2',
            'percentage_value' => 'decimal:2',
            'internal_rate' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
