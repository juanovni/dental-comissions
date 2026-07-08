<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('social_crm_settings')->insertOrIgnore([
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_clinic_days',
                'label' => 'Dias laborables',
                'value_type' => 'array',
                'value' => json_encode([1, 2, 3, 4, 5]),
                'notes' => 'Dias de la semana en que la clinica atiende (0=Domingo, 1=Lunes ... 6=Sabado).',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_clinic_open',
                'label' => 'Hora de apertura',
                'value_type' => 'string',
                'value' => json_encode('09:00'),
                'notes' => 'Hora de inicio de atencion (formato HH:MM).',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_clinic_close',
                'label' => 'Hora de cierre',
                'value_type' => 'string',
                'value' => json_encode('18:00'),
                'notes' => 'Hora de fin de atencion (formato HH:MM).',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_slot_duration',
                'label' => 'Duracion de cita (minutos)',
                'value_type' => 'integer',
                'value' => json_encode(45),
                'notes' => 'Duracion de cada espacio en minutos.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_lead_time_hours',
                'label' => 'Anticipacion minima (horas)',
                'value_type' => 'integer',
                'value' => json_encode(2),
                'notes' => 'Minimo de horas de anticipacion desde ahora para ofrecer un slot.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_max_slots_offer',
                'label' => 'Maximo de slots a ofrecer',
                'value_type' => 'integer',
                'value' => json_encode(3),
                'notes' => 'Cantidad maxima de slots que el bot propone al paciente.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_auto_confirm',
                'label' => 'Auto-confirmar cita',
                'value_type' => 'boolean',
                'value' => json_encode(false),
                'notes' => 'Si esta activo, crea la cita automaticamente cuando el paciente acepta un slot.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_group' => 'appointments',
                'key' => 'social_appointment_propose_slots',
                'label' => 'Proponer slots reales',
                'value_type' => 'boolean',
                'value' => json_encode(false),
                'notes' => 'Muestra horarios reales disponibles en la respuesta del bot cuando el paciente muestra interes en agendar.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('social_crm_settings')
            ->whereIn('key', [
                'social_appointment_clinic_days',
                'social_appointment_clinic_open',
                'social_appointment_clinic_close',
                'social_appointment_slot_duration',
                'social_appointment_lead_time_hours',
                'social_appointment_max_slots_offer',
                'social_appointment_auto_confirm',
                'social_appointment_propose_slots',
            ])
            ->delete();
    }
};
