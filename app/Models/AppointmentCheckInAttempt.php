<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentCheckInAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'clinic_slug',
        'channel',
        'status',
        'failure_reason',
        'identifier_hash',
        'identifier_last_digits',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
