<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'social_comment_id',
        'social_identity_id',
        'social_post_id',
        'procedure_id',
        'doctor_id',
        'assigned_user_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'source',
        'notes',
        'created_by',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'no_show_at',
        'metadata',
        'external_provider',
        'external_appointment_id',
        'external_calendar_id',
        'external_status',
        'external_payload',
        'last_synced_at',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'source' => AppointmentSource::class,
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_at' => 'datetime',
            'metadata' => 'array',
            'external_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function socialComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class);
    }

    public function socialIdentity(): BelongsTo
    {
        return $this->belongsTo(SocialIdentity::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'doctor_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasCalendarSync(): bool
    {
        return $this->doctor_id
            && app(\App\Services\GoogleCalendarService::class)->hasClinicCalendar()
            && $this->external_appointment_id !== null;
    }

    public function isSynced(): bool
    {
        return $this->hasCalendarSync()
            && $this->external_status === 'active'
            && $this->sync_error === null;
    }

    public function confirm(): void
    {
        $this->update([
            'status' => AppointmentStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => AppointmentStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function markNoShow(): void
    {
        $this->update([
            'status' => AppointmentStatus::NoShow,
            'no_show_at' => now(),
        ]);
    }
}
