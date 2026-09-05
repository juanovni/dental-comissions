<?php

namespace App\Models;

use App\Enums\ExternalConsultationStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExternalMedicalConsultation extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'requesting_professional_id',
        'recipient_professional_id',
        'specialty_id',
        'recipient_name',
        'recipient_institution',
        'recipient_phone',
        'recipient_email',
        'specialty_name',
        'reason',
        'clinical_context',
        'questions',
        'supporting_documents',
        'status',
        'requested_at',
        'sent_at',
        'received_at',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExternalConsultationStatus::class,
            'questions' => 'array',
            'requested_at' => 'datetime',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function requestingProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'requesting_professional_id');
    }

    public function recipientProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'recipient_professional_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MedicalClearanceDecision::class, 'consultation_id');
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(MedicalClearanceDecision::class, 'consultation_id')
            ->latestOfMany('created_at');
    }
}
