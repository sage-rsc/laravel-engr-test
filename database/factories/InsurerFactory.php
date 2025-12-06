<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Insurer>
 */
class InsurerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => 'INS-' . fake()->unique()->lexify('???'),
            'email' => fake()->unique()->safeEmail(),
            'daily_capacity' => fake()->numberBetween(500, 2000),
            'min_batch_size' => fake()->numberBetween(5, 20),
            'max_batch_size' => fake()->numberBetween(50, 200),
            'date_preference' => fake()->randomElement(['encounter', 'submission']),
            'specialty_efficiencies' => null,
        ];
    }
}
