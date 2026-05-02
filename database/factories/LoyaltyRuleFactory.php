<?php

namespace Database\Factories;

use App\Models\LoyaltyRule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyRule>
 */
class LoyaltyRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'points_earned' => fake()->numberBetween(1, 100),
            'discount_pct' => fake()->randomFloat(2, 0, 50),
            'valid_months' => fake()->optional()->numberBetween(1, 24),
            'is_active' => true,
        ];
    }
}
