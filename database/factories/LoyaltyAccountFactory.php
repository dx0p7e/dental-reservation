<?php

namespace Database\Factories;

use App\Models\LoyaltyAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyAccount>
 */
class LoyaltyAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(['role' => 'doctor']),
            'points_balance' => 0,
            'tier' => 'standard',
        ];
    }
}
