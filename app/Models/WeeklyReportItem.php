<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekly_report_id',
        'activity_record_id',
    ];

    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    public function activityRecord(): BelongsTo
    {
        return $this->belongsTo(ActivityRecord::class);
    }
}
