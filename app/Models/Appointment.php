<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
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
        'checked_in_at',
        'preparation_started_at',
        'ready_for_doctor_at',
        'consultation_started_at',
        'consultation_finished_at',
        'cancelled_at',
        'completed_at',
        'no_show_at',
        'check_in_source',
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
            'checked_in_at' => 'datetime',
            'preparation_started_at' => 'datetime',
            'ready_for_doctor_at' => 'datetime',
            'consultation_started_at' => 'datetime',
            'consultation_finished_at' => 'datetime',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
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

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class);
    }

    public function appointmentNotes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class)->latest();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }

    public function latestAppointmentNote(): HasOne
    {
        return $this->hasOne(AppointmentNote::class)->latestOfMany();
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

    public function waitingMinutes(): ?int
    {
        if (! $this->checked_in_at) {
            return null;
        }

        $end = $this->consultation_started_at ?? now();

        return (int) $this->checked_in_at->diffInMinutes($end);
    }

    public function consultationMinutes(): ?int
    {
        if (! $this->consultation_started_at) {
            return null;
        }

        $end = $this->consultation_finished_at ?? now();

        return (int) $this->consultation_started_at->diffInMinutes($end);
    }

    public function waitingStatusColor(int $warningMinutes = 15, int $dangerMinutes = 30): string
    {
        $minutes = $this->waitingMinutes();

        if ($minutes === null) {
            return 'gray';
        }

        if ($minutes >= $dangerMinutes) {
            return 'danger';
        }

        if ($minutes >= $warningMinutes) {
            return 'warning';
        }

        return 'success';
    }

    public function confirm(): void
    {
        app(\App\Services\AppointmentFlowService::class)->transition($this, AppointmentStatus::Confirmed);
    }

    public function cancel(): void
    {
        app(\App\Services\AppointmentFlowService::class)->transition($this, AppointmentStatus::Cancelled);
    }

    public function complete(): void
    {
        app(\App\Services\AppointmentFlowService::class)->transition($this, AppointmentStatus::Completed);
    }

    public function markNoShow(): void
    {
        app(\App\Services\AppointmentFlowService::class)->transition($this, AppointmentStatus::NoShow);
    }
}
