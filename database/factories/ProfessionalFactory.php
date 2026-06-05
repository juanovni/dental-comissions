<?php

namespace Database\Factories;

use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfessionalFactory extends Factory
{
    protected $model = Professional::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => 'doctor',
            'whatsapp_phone' => '+' . $this->faker->numerify('57300#######'),
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
            'can_register_via_whatsapp' => true,
            'notes' => null,
        ];
    }

    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'doctor']);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'assistant']);
    }
}
