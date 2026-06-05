<?php

namespace Database\Seeders;

use App\Enums\ProfessionalRole;
use App\Models\DoctorAssistantAssignment;
use App\Models\Professional;
use Illuminate\Database\Seeder;

class ProfessionalSeeder extends Seeder
{
    public function run(): void
    {
        $doctor1 = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573001112233'],
            [
                'name' => 'Dr. Carlos Rodriguez',
                'role' => ProfessionalRole::Doctor,
                'email' => 'carlos@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => true,
            ]
        );

        $doctor2 = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573004445566'],
            [
                'name' => 'Dra. Maria Lopez',
                'role' => ProfessionalRole::Doctor,
                'email' => 'maria@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => true,
            ]
        );

        $assistant1 = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573007778899'],
            [
                'name' => 'Ana Garcia',
                'role' => ProfessionalRole::Assistant,
                'email' => 'ana@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => false,
            ]
        );

        $assistant2 = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573000001122'],
            [
                'name' => 'Luis Martinez',
                'role' => ProfessionalRole::Assistant,
                'email' => 'luis@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => false,
            ]
        );

        $assistant3 = Professional::updateOrCreate(
            ['whatsapp_phone' => '+573003334455'],
            [
                'name' => 'Sofia Hernandez',
                'role' => ProfessionalRole::Assistant,
                'email' => 'sofia@clinica.com',
                'is_active' => true,
                'can_register_via_whatsapp' => false,
            ]
        );

        DoctorAssistantAssignment::updateOrCreate(
            ['doctor_id' => $doctor1->id, 'assistant_id' => $assistant1->id],
            ['is_active' => true]
        );

        DoctorAssistantAssignment::updateOrCreate(
            ['doctor_id' => $doctor1->id, 'assistant_id' => $assistant2->id],
            ['is_active' => true]
        );

        DoctorAssistantAssignment::updateOrCreate(
            ['doctor_id' => $doctor2->id, 'assistant_id' => $assistant2->id],
            ['is_active' => true]
        );

        DoctorAssistantAssignment::updateOrCreate(
            ['doctor_id' => $doctor2->id, 'assistant_id' => $assistant3->id],
            ['is_active' => true]
        );
    }
}
