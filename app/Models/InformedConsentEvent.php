<?php

namespace App\Models;

use App\Enums\ConsentEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformedConsentEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'consent_id',
        'event_type',
        'from_status',
        'to_status',
        'occurred_at',
        'created_by',
        'professional_id',
        'source',
        'notes',
        'metadata',
        'request_id',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => ConsentEventType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function consent(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItemConsent::class, 'consent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }
}
