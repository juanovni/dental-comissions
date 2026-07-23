<?php

namespace Database\Factories;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Models\VoiceCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoiceCallFactory extends Factory
{
    protected $model = VoiceCall::class;

    public function definition(): array
    {
        return [
            'channel' => VoiceChannelType::WebTest,
            'provider' => 'web_test',
            'from_phone' => $this->faker->e164PhoneNumber(),
            'to_phone' => $this->faker->e164PhoneNumber(),
            'status' => VoiceCallStatus::Started,
            'started_at' => now(),
            'metadata' => ['source' => 'factory'],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VoiceCallStatus::Completed,
            'ended_at' => now()->addMinutes(5),
            'duration_seconds' => 300,
        ]);
    }

    public function handoffRequired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VoiceCallStatus::HandoffRequired,
        ]);
    }
}
