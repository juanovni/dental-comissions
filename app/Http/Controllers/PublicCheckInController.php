<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentCheckInAttempt;
use App\Services\AppointmentFlowService;
use App\Services\SocialCrmSettingsService;
use Carbon\Carbon;
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
            return $this->checkInAppointment((int) $data['appointment_id'], $clinicSlug, $request);
        }

        $appointments = $this->findTodayAppointments((string) $data['identifier']);

        if ($appointments->isEmpty()) {
            $this->recordFailedAttempt($request, $clinicSlug, 'not_found', (string) $data['identifier']);

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

        return $this->checkInAppointment((int) $appointments->first()->id, $clinicSlug, $request);
    }

    private function checkInAppointment(int $appointmentId, string $clinicSlug, Request $request): RedirectResponse
    {
        $appointment = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereBetween('scheduled_at', $this->todayBounds())
            ->find($appointmentId);

        if (! $appointment) {
            $this->recordFailedAttempt($request, $clinicSlug, 'appointment_not_found', (string) $appointmentId);

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
            $this->recordFailedAttempt($request, $clinicSlug, 'unavailable_status', (string) $appointmentId, $appointment, [
                'appointment_status' => $appointment->status->value,
            ]);

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

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordFailedAttempt(
        Request $request,
        string $clinicSlug,
        string $reason,
        ?string $identifier = null,
        ?Appointment $appointment = null,
        array $metadata = [],
    ): void {
        $identifier = trim((string) $identifier);
        $digits = preg_replace('/\D+/', '', $identifier) ?: '';

        AppointmentCheckInAttempt::create([
            'appointment_id' => $appointment?->id,
            'clinic_slug' => $clinicSlug,
            'channel' => 'public',
            'status' => 'failed',
            'failure_reason' => $reason,
            'identifier_hash' => $identifier !== '' ? hash('sha256', mb_strtolower($identifier)) : null,
            'identifier_last_digits' => $digits !== '' ? substr($digits, -4) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function findTodayAppointments(string $identifier): Collection
    {
        $identifier = trim($identifier);
        $digits = preg_replace('/\D+/', '', $identifier) ?? '';

        if ($digits === '' && ! ctype_digit($identifier)) {
            return collect();
        }

        $phoneVariants = $this->phoneSearchVariants($digits);

        return Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereBetween('scheduled_at', $this->todayBounds())
            ->where(function ($query) use ($identifier, $phoneVariants): void {
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }

                if ($phoneVariants !== []) {
                    $query->orWhereHas('patient', function ($query) use ($phoneVariants): void {
                        $query->where(function ($query) use ($phoneVariants): void {
                            foreach ($phoneVariants as $variant) {
                                $query->orWhereRaw("regexp_replace(coalesce(phone, ''), '[^0-9]', '', 'g') like ?", ['%'.$variant]);
                            }
                        });
                    });
                }
            })
            ->orderBy('scheduled_at')
            ->get();
    }

    private function todayBounds(): array
    {
        $todayStart = Carbon::now(app(SocialCrmSettingsService::class)->clinicTimezone())->startOfDay();

        return [$todayStart, $todayStart->copy()->endOfDay()];
    }

    /**
     * @return array<int, string>
     */
    private function phoneSearchVariants(string $digits): array
    {
        if ($digits === '') {
            return [];
        }

        $variants = [$digits];

        if (str_starts_with($digits, '593') && strlen($digits) > 3) {
            $variants[] = '0'.substr($digits, 3);
        }

        if (str_starts_with($digits, '0') && strlen($digits) > 1) {
            $variants[] = '593'.substr($digits, 1);
        }

        if (strlen($digits) >= 9) {
            $variants[] = substr($digits, -9);
        }

        return array_values(array_unique($variants));
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
