<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItemAppointment extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'treatment_plan_item_id',
        'appointment_id',
        'planned_quantity',
        'completed_quantity',
        'session_number',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'integer',
            'completed_quantity' => 'integer',
            'session_number' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
