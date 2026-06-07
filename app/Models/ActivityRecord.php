<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class ActivityRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'procedure_id',
        'payment_method_id',
        'payment_method_raw',
        'payment_method_commission_snapshot',
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
            'payment_method_commission_snapshot' => 'decimal:2',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
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

        $this->internal_rate_snapshot = $procedure->internal_rate;

        $commissionRate = $this->payment_method_id
            ? PaymentMethodCommissionRate::where('payment_method_id', $this->payment_method_id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $this->activity_date);
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $this->activity_date);
                })
                ->latest('starts_at')
                ->first()
            : null;

        $amount = $commissionRate ? (float) $commissionRate->amount : 0;

        $this->doctor_commission_amount = $amount;
        $this->payment_method_commission_snapshot = $amount;

        $assistantTotal = 0;

        foreach ($this->assistants as $assistant) {
            $this->assistants()->updateExistingPivot($assistant->id, [
                'commission_amount' => 0,
            ]);
        }

        $this->assistant_commission_total = $assistantTotal;
        $this->save();
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
