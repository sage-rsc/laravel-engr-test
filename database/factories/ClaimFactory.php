<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'insurer_id' => \App\Models\Insurer::factory(),
            'provider_name' => fake()->company(),
            'encounter_date' => fake()->date(),
            'submission_date' => fake()->date(),
            'priority_level' => fake()->numberBetween(1, 5),
            'specialty' => fake()->randomElement(['cardiology', 'orthopedics', 'pediatrics', 'neurology']),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'batch_id' => null,
        ];
    }
}
