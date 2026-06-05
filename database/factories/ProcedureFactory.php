<?php

namespace Database\Factories;

use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcedureFactory extends Factory
{
    protected $model = Procedure::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'code' => strtoupper($this->faker->unique()->bothify('???###')),
            'category' => $this->faker->randomElement(['Preventiva', 'Cirugia', 'Restaurativa', 'Protesis']),
            'internal_rate' => $this->faker->randomFloat(2, 20, 500),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
