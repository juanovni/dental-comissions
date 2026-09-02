<?php

namespace App\Models;

use App\Enums\OdontogramEntryType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdontogramConditionCatalog extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'code',
        'name',
        'category',
        'entry_type',
        'symbol',
        'color',
        'applies_to',
        'requires_surface',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => OdontogramEntryType::class,
            'requires_surface' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }
}
