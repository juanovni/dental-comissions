<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentFlowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicCheckInController extends Controller
{
    public function show(string $clinicSlug): View
    {
        return view('patient-flow.check-in', [
            'clinicSlug' => $clinicSlug,
            'status' => session('check_in_status'),
            'appointments' => session('check_in_options', []),
        ]);
    }

    public function store(Request $request, string $clinicSlug): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required_without:appointment_id', 'nullable', 'string', 'max:80'],
            'appointment_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['appointment_id'])) {
            return $this->checkInAppointment((int) $data['appointment_id'], $clinicSlug);
        }

        $appointments = $this->findTodayAppointments((string) $data['identifier']);

        if ($appointments->isEmpty()) {
            return redirect()
                ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
                ->with('check_in_status', [
                    'type' => 'not_found',
                    'title' => 'No encontramos tu cita',
                    'message' => 'No encontramos una cita para hoy con ese dato. Acercate a recepcion para ayudarte.',
                ]);
        }

        if ($appointments->count() > 1) {
            return redirect()
                ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
                ->with('check_in_status', [
                    'type' => 'multiple',
                    'title' => 'Selecciona tu cita',
                    'message' => 'Encontramos mas de una cita para hoy.',
                ])
                ->with('check_in_options', $appointments->map(fn (Appointment $appointment): array => [
                    'id' => $appointment->id,
                    'time' => $appointment->scheduled_at?->format('h:i a'),
                    'doctor' => $appointment->doctor?->name,
                ])->all());
        }

        return $this->checkInAppointment((int) $appointments->first()->id, $clinicSlug);
    }

    private function checkInAppointment(int $appointmentId, string $clinicSlug): RedirectResponse
    {
        $appointment = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereDate('scheduled_at', today())
            ->find($appointmentId);

        if (! $appointment) {
            return redirect()
                ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
                ->with('check_in_status', [
                    'type' => 'not_found',
                    'title' => 'No encontramos tu cita',
                    'message' => 'No encontramos una cita para hoy con ese dato. Acercate a recepcion para ayudarte.',
                ]);
        }

        if ($appointment->status === AppointmentStatus::CheckedIn || $appointment->checked_in_at) {
            return redirect()
                ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
                ->with('check_in_status', $this->successStatus('Tu llegada ya fue confirmada anteriormente.', $appointment));
        }

        if (in_array($appointment->status, [AppointmentStatus::Cancelled, AppointmentStatus::Rescheduled, AppointmentStatus::NoShow, AppointmentStatus::Completed], true)) {
            return redirect()
                ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
                ->with('check_in_status', [
                    'type' => 'unavailable',
                    'title' => 'Cita no disponible',
                    'message' => 'Esta cita no esta disponible para check-in. Acercate a recepcion para ayudarte.',
                ]);
        }

        app(AppointmentFlowService::class)->transition(
            $appointment,
            AppointmentStatus::CheckedIn,
            'qr',
            null,
            ['check_in_source' => 'qr'],
        );

        return redirect()
            ->route('patient-flow.check-in.show', ['clinicSlug' => $clinicSlug])
            ->with('check_in_status', $this->successStatus('Recepcion fue notificada de tu llegada.', $appointment->fresh(['doctor'])));
    }

    private function findTodayAppointments(string $identifier): Collection
    {
        $identifier = trim($identifier);
        $digits = preg_replace('/\D+/', '', $identifier) ?? '';

        if ($digits === '' && ! ctype_digit($identifier)) {
            return collect();
        }

        return Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereDate('scheduled_at', today())
            ->where(function ($query) use ($identifier, $digits): void {
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }

                if ($digits !== '') {
                    $query->orWhereHas('patient', function ($query) use ($digits): void {
                        $query->whereRaw("regexp_replace(coalesce(phone, ''), '[^0-9]', '', 'g') like ?", ['%'.$digits]);
                    });
                }
            })
            ->orderBy('scheduled_at')
            ->get();
    }

    private function successStatus(string $message, Appointment $appointment): array
    {
        return [
            'type' => 'success',
            'title' => 'Gracias',
            'message' => $message,
            'state' => 'En espera',
            'updated' => 'justo ahora',
            'time' => $appointment->scheduled_at?->format('h:i a'),
            'doctor' => $appointment->doctor?->name,
        ];
    }
}
