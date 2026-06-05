<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivityRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'procedure_id',
        'activity_date',
        'activity_time',
        'status',
        'doctor_commission_amount',
        'assistant_commission_total',
        'internal_rate_snapshot',
        'correction_notes',
        'approved_at',
        'approved_by',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'activity_date' => 'date',
            'doctor_commission_amount' => 'decimal:2',
            'assistant_commission_total' => 'decimal:2',
            'internal_rate_snapshot' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'doctor_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'activity_assistants', 'activity_record_id', 'assistant_id')
            ->withPivot(['commission_amount', 'notes'])
            ->withTimestamps();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateCommissions(): void
    {
        $procedure = $this->procedure;
        $doctor = $this->doctor;

        $this->internal_rate_snapshot = $procedure->internal_rate;

        $doctorRule = CommissionRule::where('professional_id', $doctor->id)
            ->where('procedure_id', $procedure->id)
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $this->activity_date);
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $this->activity_date);
            })
            ->first();

        if (!$doctorRule) {
            $doctorRule = CommissionRule::whereNull('professional_id')
                ->where('procedure_id', $procedure->id)
                ->where('role', 'doctor')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $this->activity_date);
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $this->activity_date);
                })
                ->first();
        }

        $this->doctor_commission_amount = $doctorRule
            ? $this->computeAmount($doctorRule, $procedure->internal_rate)
            : 0;

        $assistantTotal = 0;

        foreach ($this->assistants as $assistant) {
            $assistantRule = CommissionRule::where('professional_id', $assistant->id)
                ->where('procedure_id', $procedure->id)
                ->where('role', 'assistant')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $this->activity_date);
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $this->activity_date);
                })
                ->first();

            if (!$assistantRule) {
                $assistantRule = CommissionRule::whereNull('professional_id')
                    ->where('procedure_id', $procedure->id)
                    ->where('role', 'assistant')
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('starts_at')
                            ->orWhere('starts_at', '<=', $this->activity_date);
                    })
                    ->where(function ($query) {
                        $query->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', $this->activity_date);
                    })
                    ->first();
            }

            $amount = $assistantRule
                ? $this->computeAmount($assistantRule, $procedure->internal_rate)
                : 0;

            $this->assistants()->updateExistingPivot($assistant->id, [
                'commission_amount' => $amount,
            ]);

            $assistantTotal += $amount;
        }

        $this->assistant_commission_total = $assistantTotal;
        $this->save();
    }

    private function computeAmount(CommissionRule $rule, ?float $internalRate): float
    {
        return match ($rule->commission_type) {
            CommissionType::FixedPerProcedure => (float) $rule->fixed_amount,
            CommissionType::PercentageOfInternalRate => ($internalRate ?? 0) * ($rule->percentage_value / 100),
            CommissionType::Mixed => (float) $rule->fixed_amount + (($internalRate ?? 0) * ($rule->percentage_value / 100)),
            CommissionType::None => 0,
        };
    }

    public function approve(): void
    {
        $this->update([
            'status' => ActivityStatus::Approved,
            'approved_at' => Carbon::now(),
            'approved_by' => Auth::id(),
            'correction_notes' => null,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => ActivityStatus::Paid,
            'paid_at' => Carbon::now(),
        ]);
    }

    public function requestCorrection(string $notes): void
    {
        $this->update([
            'status' => ActivityStatus::NeedsReview,
            'correction_notes' => $notes,
        ]);
    }
}
