<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Efectivo',
                'code' => 'EFECTIVO',
                'aliases' => ['efectivo', 'efe', 'ef', 'cash', 'contado'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Transferencia',
                'code' => 'TRANSFERENCIA',
                'aliases' => ['transferencia', 'transf', 'transfer', 'transferido'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Credito',
                'code' => 'CREDITO',
                'aliases' => ['credito', 'crédito', 'tc', 'tarjeta credito', 'tarjeta crédito'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Debito',
                'code' => 'DEBITO',
                'aliases' => ['debito', 'débito', 'td', 'tarjeta debito', 'tarjeta débito'],
                'sort_order' => 4,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [...$method, 'is_active' => true],
            );
        }
    }
}
