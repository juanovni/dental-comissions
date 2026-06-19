<?php

namespace App\Models;

use App\Enums\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'whatsapp_phone',
        'email',
        'is_active',
        'can_register_via_whatsapp',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProfessionalRole::class,
            'is_active' => 'boolean',
            'can_register_via_whatsapp' => 'boolean',
        ];
    }

    public function assignedAssistants(): BelongsToMany
    {
        return $this->belongsToMany(
            Professional::class,
            'doctor_assistant_assignments',
            'doctor_id',
            'assistant_id',
        )->withPivot(['is_active', 'starts_at', 'ends_at'])->withTimestamps();
    }

    public function assignedDoctors(): BelongsToMany
    {
        return $this->belongsToMany(
            Professional::class,
            'doctor_assistant_assignments',
            'assistant_id',
            'doctor_id',
        )->withPivot(['is_active', 'starts_at', 'ends_at'])->withTimestamps();
    }

}
