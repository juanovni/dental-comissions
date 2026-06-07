<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodCommissionRate;
use Illuminate\Database\Seeder;

class PaymentMethodCommissionRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            'EFECTIVO' => 1.25,
            'TRANSFERENCIA' => 1.25,
            'CREDITO' => 1.06,
            'DEBITO' => 1.19,
        ];

        foreach ($rates as $code => $amount) {
            $paymentMethod = PaymentMethod::where('code', $code)->first();

            if (!$paymentMethod) {
                continue;
            }

            PaymentMethodCommissionRate::updateOrCreate(
                [
                    'payment_method_id' => $paymentMethod->id,
                    'starts_at' => null,
                    'ends_at' => null,
                ],
                [
                    'amount' => $amount,
                    'is_active' => true,
                ],
            );
        }
    }
}
