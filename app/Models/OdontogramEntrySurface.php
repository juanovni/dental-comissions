<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramEntrySurface extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'odontogram_entry_id',
        'surface',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(OdontogramEntry::class, 'odontogram_entry_id');
    }
}
