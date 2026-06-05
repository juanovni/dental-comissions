<?php

namespace Database\Seeders;

use App\Enums\CommissionType;
use App\Enums\ProfessionalRole;
use App\Models\CommissionRule;
use App\Models\Procedure;
use App\Models\Professional;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = Procedure::where('is_active', true)->get();
        $doctors = Professional::where('role', ProfessionalRole::Doctor)->where('is_active', true)->get();
        $assistants = Professional::where('role', ProfessionalRole::Assistant)->where('is_active', true)->get();

        foreach ($doctors as $doctor) {
            foreach ($procedures as $procedure) {
                CommissionRule::updateOrCreate(
                    [
                        'professional_id' => $doctor->id,
                        'procedure_id' => $procedure->id,
                        'role' => 'doctor',
                    ],
                    [
                        'name' => "Comision doctor - {$procedure->name}",
                        'commission_type' => CommissionType::PercentageOfInternalRate,
                        'percentage_value' => 30.00,
                        'is_active' => true,
                    ]
                );
            }
        }

        foreach ($assistants as $assistant) {
            foreach ($procedures as $procedure) {
                CommissionRule::updateOrCreate(
                    [
                        'professional_id' => $assistant->id,
                        'procedure_id' => $procedure->id,
                        'role' => 'assistant',
                    ],
                    [
                        'name' => "Comision auxiliar - {$procedure->name}",
                        'commission_type' => CommissionType::FixedPerProcedure,
                        'fixed_amount' => 10.00,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
