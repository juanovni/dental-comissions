<?php

namespace App\Http\Controllers;

use App\Models\Procedure;
use App\Models\Professional;
use App\Services\VoiceToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceToolController extends Controller
{
    public function __construct(
        private VoiceToolService $toolService,
    ) {}

    public function identifyPatient(Request $request): JsonResponse
    {
        $result = $this->toolService->identifyPatient(
            $request->validate(['phone_e164' => 'required|string|max:20']),
        );

        return response()->json($result);
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'procedure_name' => 'nullable|string|max:255',
            'preferred_date' => 'nullable|date',
            'preferred_period' => 'nullable|string|in:morning,afternoon',
            'doctor_id' => ['nullable', 'integer', $this->tenantScopedExists(Professional::class)],
        ]);

        return response()->json($this->toolService->getAvailableSlots($data));
    }

    public function holdSlot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot_datetime' => 'required|date',
            'doctor_id' => ['required', 'integer', $this->tenantScopedExists(Professional::class)],
            'procedure_id' => ['required', 'integer', $this->tenantScopedExists(Procedure::class)],
            'phone_e164' => 'nullable|string|max:20',
        ]);

        return response()->json($this->toolService->holdSlot($data));
    }

    public function createAppointment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hold_token' => 'required|string',
            'patient_name' => 'required|string|max:255',
            'phone_e164' => 'required|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        return response()->json($this->toolService->createAppointment($data));
    }

    public function requestHandoff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:100',
            'summary' => 'nullable|string|max:1000',
        ]);

        return response()->json($this->toolService->requestHandoff($data));
    }

    private function tenantScopedExists(string $model): \Closure
    {
        return function (string $attribute, mixed $value, $fail) use ($model): void {
            if ($value === null) {
                return;
            }

            $exists = $model::query()
                ->forCurrentTenant()
                ->whereKey((int) $value)
                ->exists();

            if (! $exists) {
                $fail('El registro seleccionado no pertenece a esta clinica.');
            }
        };
    }
}
