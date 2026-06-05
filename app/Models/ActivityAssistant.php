<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssistant extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_record_id',
        'assistant_id',
        'commission_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
        ];
    }

    public function activityRecord(): BelongsTo
    {
        return $this->belongsTo(ActivityRecord::class);
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'assistant_id');
    }
}
