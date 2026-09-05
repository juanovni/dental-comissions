<?php

namespace App\Models;

use App\Enums\LaboratoryCaseEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalLaboratoryEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'case_id',
        'case_item_id',
        'event_type',
        'notes',
        'professional_id',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => LaboratoryCaseEventType::class,
            'metadata' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalLaboratoryCase::class, 'case_id');
    }

    public function caseItem(): BelongsTo
    {
        return $this->belongsTo(DentalLaboratoryCaseItem::class, 'case_item_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
