<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $name = $this->faker->name();
        return [
            'full_name' => $name,
            'normalized_name' => Str::of($name)->lower()->ascii()->squish()->toString(),
            'phone' => $this->faker->optional()->numerify('+57300#######'),
            'date_of_birth' => $this->faker->optional()->date(),
            'notes' => null,
        ];
    }
}
