<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['tier' => 'standard', 'points_threshold' => 0,    'discount_bonus_pct' => 0.00],
            ['tier' => 'silver',   'points_threshold' => 500,  'discount_bonus_pct' => 5.00],
            ['tier' => 'gold',     'points_threshold' => 1500, 'discount_bonus_pct' => 10.00],
        ];

        foreach ($tiers as $data) {
            LoyaltyTier::updateOrCreate(['tier' => $data['tier']], $data);
        }
    }
}
