<?php

namespace Database\Factories;

use App\Models\LoyaltyTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyTier>
 */
class LoyaltyTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tier' => fake()->unique()->randomElement(['standard', 'silver', 'gold']),
            'points_threshold' => fake()->numberBetween(0, 1000),
            'discount_bonus_pct' => fake()->randomFloat(2, 0, 20),
            'color' => fake()->randomElement(['#6b7280', '#94a3b8', '#f59e0b']),
        ];
    }
}
