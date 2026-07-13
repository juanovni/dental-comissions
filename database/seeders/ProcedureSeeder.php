<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = [
            ['name' => 'Limpieza dental', 'code' => 'LIMP001', 'category' => 'Preventiva', 'internal_rate' => 50.00],
            ['name' => 'Extracción simple', 'code' => 'EXT001', 'category' => 'Cirugia', 'internal_rate' => 80.00],
            ['name' => 'Endodoncia', 'code' => 'END001', 'category' => 'Endodoncia', 'internal_rate' => 200.00],
            ['name' => 'Blanqueamiento', 'code' => 'BLA001', 'category' => 'Estetica', 'internal_rate' => 120.00],
            ['name' => 'Ortodoncia invisible', 'code' => 'ORT002', 'category' => 'Ortodoncia', 'internal_rate' => 200.00],
            ['name' => 'Radiografía panorámica', 'code' => 'RAD001', 'category' => 'Diagnostico', 'internal_rate' => 25.00],
        ];

        foreach ($procedures as $procedure) {
            Procedure::updateOrCreate(
                ['code' => $procedure['code']],
                $procedure
            );
        }
    }
}
