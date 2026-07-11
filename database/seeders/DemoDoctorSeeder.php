<?php

namespace Database\Seeders;

use App\Enums\ProfessionalRole;
use App\Models\DoctorAssistantAssignment;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProcedureSeeder::class);

        $doctor = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573001112200'],
            [
                'name' => 'Dr. Demo',
                'role' => ProfessionalRole::Doctor,
                'email' => 'demo@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => true,
                'google_calendar_enabled' => false,
                'google_calendar_email' => null,
            ]
        );

        $assistant = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573007778800'],
            [
                'name' => 'Asistente Demo',
                'role' => ProfessionalRole::Assistant,
                'email' => 'asistente.demo@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => false,
            ]
        );

        DoctorAssistantAssignment::updateOrCreate(
            ['doctor_id' => $doctor->id, 'assistant_id' => $assistant->id],
            ['is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'admin@clinica.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
            ]
        );

        $this->command->info('Demo doctor created: Dr. Demo (demo@clinica.com)');
        $this->command->info('Assistant created: Asistente Demo (asistente.demo@clinica.com)');
        $this->command->info('Admin user: admin@clinica.com / password');
        $this->command->info('Connect Google Calendar at /admin/integrations#google-calendar');
    }
}
