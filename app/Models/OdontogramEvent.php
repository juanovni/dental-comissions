<?php

namespace App\Models;

use App\Enums\OdontogramEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramEvent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'odontogram_id',
        'odontogram_entry_id',
        'event_type',
        'occurred_at',
        'created_by',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => OdontogramEventType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function odontogram(): BelongsTo
    {
        return $this->belongsTo(Odontogram::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OdontogramEntry::class, 'odontogram_entry_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'created_by');
    }
}
