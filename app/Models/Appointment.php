<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'checked_in_at',
        'on_the_way_at',
        'consultation_started_at',
        'consultation_finished_at',
        'room',
        'waiting_time_minutes',
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
            'checked_in_at' => 'datetime',
            'on_the_way_at' => 'datetime',
            'consultation_started_at' => 'datetime',
            'consultation_finished_at' => 'datetime',
            'waiting_time_minutes' => 'integer',
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

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class);
    }

    public function waitingTime(): ?int
    {
        if (! $this->checked_in_at) {
            return null;
        }

        $end = $this->consultation_started_at ?? now();

        return (int) $this->checked_in_at->diffInMinutes($end);
    }

    public function consultationDuration(): ?int
    {
        if (! $this->consultation_started_at || ! $this->consultation_finished_at) {
            return null;
        }

        return (int) $this->consultation_started_at->diffInMinutes($this->consultation_finished_at);
    }

    public function isLate(): bool
    {
        if (! $this->scheduled_at || ! $this->checked_in_at) {
            return false;
        }

        return $this->checked_in_at->greaterThan($this->scheduled_at);
    }

    public function lateMinutes(): int
    {
        if (! $this->isLate() || ! $this->checked_in_at || ! $this->scheduled_at) {
            return 0;
        }

        return (int) $this->scheduled_at->diffInMinutes($this->checked_in_at, false);
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

    public function markOnTheWay(): void
    {
        $this->update([
            'status' => AppointmentStatus::OnTheWay,
            'on_the_way_at' => now(),
        ]);
    }

    public function checkIn(): void
    {
        $this->update([
            'status' => AppointmentStatus::Waiting,
            'checked_in_at' => now(),
        ]);
    }

    public function startConsultation(string $room = null): void
    {
        $waitingMinutes = $this->checked_in_at
            ? (int) $this->checked_in_at->diffInMinutes(now())
            : null;

        $this->update([
            'status' => AppointmentStatus::InConsultation,
            'consultation_started_at' => now(),
            'room' => $room ?? $this->room,
            'waiting_time_minutes' => $waitingMinutes ?? $this->waiting_time_minutes,
        ]);
    }

    public function finishConsultation(): void
    {
        $this->update([
            'status' => AppointmentStatus::Completed,
            'completed_at' => now(),
            'consultation_finished_at' => now(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'notes' => $reason
                ? trim(($this->notes ?? '') . "\nMotivo de cancelacion: " . $reason)
                : $this->notes,
        ]);
    }

    public function complete(): void
    {
        $this->finishConsultation();
    }

    public function markNoShow(): void
    {
        $this->update([
            'status' => AppointmentStatus::NoShow,
            'no_show_at' => now(),
        ]);
    }
}
