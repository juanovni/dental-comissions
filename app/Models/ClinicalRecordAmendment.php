<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalRecordAmendment extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'clinical_encounter_id',
        'amendment_type',
        'reason',
        'changes_snapshot',
        'amended_by',
        'amended_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'changes_snapshot' => 'array',
            'amended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(ClinicalEncounter::class, 'clinical_encounter_id');
    }

    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'amended_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'approved_by');
    }
}
