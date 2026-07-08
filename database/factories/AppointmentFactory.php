<?php

namespace Database\Factories;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'procedure_id' => Procedure::factory(),
            'doctor_id' => Professional::factory()->doctor(),
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => 45,
            'status' => AppointmentStatus::PendingConfirmation,
            'source' => AppointmentSource::AdminManual,
            'notes' => null,
            'metadata' => [],
        ];
    }

    public function withDoctorWithCalendar(): static
    {
        return $this->state(function (array $attributes) {
            $doctor = Professional::factory()->doctor()->withGoogleCalendar()->create();

            return ['doctor_id' => $doctor->id];
        });
    }
}
