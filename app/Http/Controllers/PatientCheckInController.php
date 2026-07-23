<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\PatientFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientCheckInController extends Controller
{
    public function __construct(
        private PatientFlowService $patientFlowService,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $appointment = Appointment::query()
            ->with('patient')
            ->where('id', $token)
            ->orWhere('metadata->check_in_token', $token)
            ->first();

        if (! $appointment) {
            return redirect()->route('filament.admin.auth.login');
        }

        if ($appointment->status->isTerminal()) {
            return view('patient-checkin.expired', ['appointment' => $appointment]);
        }

        if ($appointment->status === \App\Enums\AppointmentStatus::Waiting) {
            return view('patient-checkin.already-checked-in', ['appointment' => $appointment]);
        }

        return view('patient-checkin.show', [
            'appointment' => $appointment,
            'patientName' => $appointment->patient?->full_name ?? 'Paciente',
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $appointment = Appointment::query()
            ->where('id', $token)
            ->orWhere('metadata->check_in_token', $token)
            ->first();

        if (! $appointment) {
            return redirect()->route('filament.admin.auth.login');
        }

        if ($appointment->status->isTerminal() || $appointment->status === \App\Enums\AppointmentStatus::Waiting) {
            return redirect()->route('filament.admin.auth.login');
        }

        $this->patientFlowService->checkIn($appointment);

        return redirect()->route('patient-checkin.done', ['token' => $token]);
    }

    public function done(string $token): View
    {
        $appointment = Appointment::query()->with('doctor')->find($token);

        return view('patient-checkin.done', [
            'appointment' => $appointment,
            'doctorName' => $appointment?->doctor?->name ?? 'el doctor',
        ]);
    }
}
