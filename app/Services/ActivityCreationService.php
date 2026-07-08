<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityRecord;
use App\Models\DoctorAssistantAssignment;
use App\Models\Patient;
use App\Models\PaymentMethodCommissionRate;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityCreationService
{
    public function create(array $parsedData, Professional $doctor, WhatsappMessage $message): ?ActivityRecord
    {
        try {
            $paymentMethodRaw = $parsedData['payment_method'] ?? null;
            $paymentMethod = app(PaymentMethodResolver::class)->resolve($paymentMethodRaw);

            if (!$paymentMethod) {
                $message->markAsFailed('Falta metodo de pago');
                return null;
            }

            $procedure = $this->matchProcedure($parsedData['procedures']);

            if (!$procedure) {
                $message->markAsNeedsReview('Procedimiento no encontrado en catalogo: ' . implode(', ', $parsedData['procedures']));
                return null;
            }

            $activityDate = $this->parseDate($parsedData['date']);

            $commissionRate = $this->findCommissionRate($paymentMethod->id, $activityDate);

            if (!$commissionRate) {
                $message->markAsFailed('No hay tarifa de comision activa para el metodo de pago: ' . $paymentMethod->name);
                return null;
            }

            $patient = $this->findOrCreatePatient($parsedData['patient_name']);

            $activity = ActivityRecord::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'procedure_id' => $procedure->id,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_raw' => $paymentMethodRaw,
                'payment_method_commission_snapshot' => $commissionRate->amount,
                'activity_date' => $activityDate,
                'status' => ActivityStatus::PendingConfirmation,
                'notes' => 'Registrado via WhatsApp - Msg ID: ' . $message->message_sid,
            ]);

            $assistantIds = $this->validateAssistants($parsedData['assistants'], $doctor);

            if (!empty($assistantIds)) {
                $activity->assistants()->sync($assistantIds);
            }

            $activity->calculateCommissions();

            app(SocialRoiService::class)->attributeActivity($activity->refresh());

            if ($parsedData['needs_review'] ?? false) {
                $activity->update([
                    'status' => ActivityStatus::NeedsReview,
                    'correction_notes' => $parsedData['review_notes'] ?? 'Requiere revision',
                ]);
            }

            return $activity;
        } catch (\Throwable $e) {
            Log::error('Error creando actividad desde WhatsApp', [
                'error' => $e->getMessage(),
                'parsed_data' => $parsedData,
                'doctor_id' => $doctor->id,
            ]);
            return null;
        }
    }

    private function findOrCreatePatient(string $name): Patient
    {
        $normalizedName = Str::of($name)->lower()->ascii()->squish()->toString();

        return Patient::firstOrCreate(
            ['normalized_name' => $normalizedName],
            [
                'full_name' => $name,
                'normalized_name' => $normalizedName,
            ]
        );
    }

    private function matchProcedure(array $procedureNames): ?Procedure
    {
        foreach ($procedureNames as $name) {
            $procedure = Procedure::where('is_active', true)
                ->where('name', $name)
                ->first();

            if ($procedure) {
                return $procedure;
            }
        }

        foreach ($procedureNames as $name) {
            $procedure = Procedure::where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($procedure) {
                return $procedure;
            }
        }

        foreach ($procedureNames as $name) {
            $normalized = Str::of($name)->lower()->ascii()->squish()->toString();
            $procedure = Procedure::where('is_active', true)
                ->whereRaw('LOWER(REPLACE(name, \'\', \'\')) LIKE ?', ["%{$normalized}%"])
                ->first();

            if ($procedure) {
                return $procedure;
            }
        }

        return null;
    }

    private function validateAssistants(array $assistantNames, Professional $doctor): array
    {
        $assignedIds = DoctorAssistantAssignment::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->pluck('assistant_id');

        $validIds = [];

        foreach ($assistantNames as $name) {
            $normalizedName = Str::of($name)->lower()->ascii()->squish()->toString();

            $assistant = Professional::whereIn('id', $assignedIds)
                ->get()
                ->first(fn (Professional $assistant): bool => Str::of($assistant->name)->lower()->ascii()->squish()->toString() === $normalizedName);

            if ($assistant) {
                $validIds[] = $assistant->id;
            }
        }

        return $validIds;
    }

    private function findCommissionRate(int $paymentMethodId, string $activityDate): ?PaymentMethodCommissionRate
    {
        return PaymentMethodCommissionRate::where('payment_method_id', $paymentMethodId)
            ->where('is_active', true)
            ->where(function ($query) use ($activityDate) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $activityDate);
            })
            ->where(function ($query) use ($activityDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $activityDate);
            })
            ->latest('starts_at')
            ->first();
    }

    private function parseDate(string $dateString): string
    {
        try {
            $date = \Carbon\Carbon::parse($dateString);
            if ($date->isFuture()) {
                return now()->format('Y-m-d');
            }
            return $date->format('Y-m-d');
        } catch (\Throwable) {
            return now()->format('Y-m-d');
        }
    }
}
