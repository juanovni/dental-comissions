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
            ['name' => 'Extraccion simple', 'code' => 'EXT001', 'category' => 'Cirugia', 'internal_rate' => 80.00],
            ['name' => 'Extraccion quirurgica', 'code' => 'EXT002', 'category' => 'Cirugia', 'internal_rate' => 150.00],
            ['name' => 'Resina simple', 'code' => 'RES001', 'category' => 'Restaurativa', 'internal_rate' => 40.00],
            ['name' => 'Resina compuesta', 'code' => 'RES002', 'category' => 'Restaurativa', 'internal_rate' => 60.00],
            ['name' => 'Endodoncia', 'code' => 'END001', 'category' => 'Endodoncia', 'internal_rate' => 200.00],
            ['name' => 'Corona metalporcelana', 'code' => 'COR001', 'category' => 'Protesis', 'internal_rate' => 250.00],
            ['name' => 'Corona zirconia', 'code' => 'COR002', 'category' => 'Protesis', 'internal_rate' => 350.00],
            ['name' => 'Puente fijo', 'code' => 'PUE001', 'category' => 'Protesis', 'internal_rate' => 400.00],
            ['name' => 'Protesis parcial removible', 'code' => 'PRO001', 'category' => 'Protesis', 'internal_rate' => 300.00],
            ['name' => 'Protesis total', 'code' => 'PRO002', 'category' => 'Protesis', 'internal_rate' => 500.00],
            ['name' => 'Blanqueamiento', 'code' => 'BLA001', 'category' => 'Estetica', 'internal_rate' => 120.00],
            ['name' => 'Laminado dental', 'code' => 'LAM001', 'category' => 'Estetica', 'internal_rate' => 300.00],
            ['name' => 'Implante dental', 'code' => 'IMP001', 'category' => 'Implantologia', 'internal_rate' => 800.00],
            ['name' => 'Cirugia periodontal', 'code' => 'CIR001', 'category' => 'Periodoncia', 'internal_rate' => 250.00],
            ['name' => 'Tratamiento periodontal', 'code' => 'TRA001', 'category' => 'Periodoncia', 'internal_rate' => 100.00],
            ['name' => 'Ortodoncia fija', 'code' => 'ORT001', 'category' => 'Ortodoncia', 'internal_rate' => 150.00],
            ['name' => 'Ortodoncia invisible', 'code' => 'ORT002', 'category' => 'Ortodoncia', 'internal_rate' => 200.00],
            ['name' => 'Radiografia panoramica', 'code' => 'RAD001', 'category' => 'Diagnostico', 'internal_rate' => 25.00],
            ['name' => 'Radiografia periapical', 'code' => 'RAD002', 'category' => 'Diagnostico', 'internal_rate' => 15.00],
        ];

        foreach ($procedures as $procedure) {
            Procedure::updateOrCreate(
                ['code' => $procedure['code']],
                $procedure
            );
        }
    }
}
