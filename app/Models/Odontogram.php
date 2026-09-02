<?php

namespace App\Models;

use App\Enums\OdontogramDentitionType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odontogram extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'dentition_type',
        'status',
        'recorded_at',
        'recorded_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'dentition_type' => OdontogramDentitionType::class,
            'recorded_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'recorded_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(OdontogramEntry::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OdontogramEvent::class);
    }
}
