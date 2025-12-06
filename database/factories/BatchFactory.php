<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
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
            'batch_date' => fake()->date(),
            'identifier' => fake()->unique()->words(3, true),
            'claim_count' => fake()->numberBetween(1, 50),
            'total_amount' => fake()->randomFloat(2, 1000, 100000),
            'estimated_cost' => fake()->randomFloat(2, 100, 10000),
            'processed' => false,
        ];
    }
}
