<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\WeeklyReportStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'week_start',
        'week_end',
        'status',
        'total_activities',
        'total_patients',
        'total_procedures',
        'total_doctor_commission',
        'total_assistant_commission',
        'total_commission',
        'notes',
        'approved_at',
        'approved_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WeeklyReportStatus::class,
            'week_start' => 'date',
            'week_end' => 'date',
            'total_activities' => 'integer',
            'total_patients' => 'integer',
            'total_procedures' => 'integer',
            'total_doctor_commission' => 'decimal:2',
            'total_assistant_commission' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyReportItem::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(ActivityRecord::class, 'weekly_report_items', 'weekly_report_id', 'activity_record_id')
            ->withTimestamps();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateForDoctor(Professional $doctor, Carbon $weekStart, Carbon $weekEnd): ?self
    {
        $existing = self::where('professional_id', $doctor->id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        if ($existing) {
            return null;
        }

        $activities = ActivityRecord::where('doctor_id', $doctor->id)
            ->whereIn('status', [ActivityStatus::Approved, ActivityStatus::Paid])
            ->whereBetween('activity_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get();

        if ($activities->isEmpty()) {
            return null;
        }

        $report = self::create([
            'professional_id' => $doctor->id,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'status' => WeeklyReportStatus::Draft,
        ]);

        $report->activities()->sync($activities->pluck('id'));

        $report->recalculateTotals();

        return $report;
    }

    public function recalculateTotals(): void
    {
        $activities = $this->activities()->get();

        $this->update([
            'total_activities' => $activities->count(),
            'total_patients' => $activities->pluck('patient_id')->unique()->count(),
            'total_procedures' => $activities->count(),
            'total_doctor_commission' => $activities->sum('doctor_commission_amount'),
            'total_assistant_commission' => 0,
            'total_commission' => $activities->sum('doctor_commission_amount'),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => WeeklyReportStatus::Approved,
            'approved_at' => Carbon::now(),
            'approved_by' => Auth::id(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => WeeklyReportStatus::Paid,
            'paid_at' => Carbon::now(),
        ]);
    }

    public function getWeekLabelAttribute(): string
    {
        return $this->week_start->format('d M') . ' - ' . $this->week_end->format('d M Y');
    }
}
