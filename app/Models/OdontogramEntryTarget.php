<?php

namespace App\Models;

use App\Enums\OdontogramTargetType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramEntryTarget extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'odontogram_entry_id',
        'target_type',
        'target_code',
        'tooth_code',
        'surface',
        'sequence_order',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => OdontogramTargetType::class,
            'sequence_order' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OdontogramEntry::class, 'odontogram_entry_id');
    }
}
