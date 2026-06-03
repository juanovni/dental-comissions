<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAssistantAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'assistant_id',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'doctor_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'assistant_id');
    }
}
