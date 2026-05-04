<?php

namespace App\Services;

use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\Service;
use App\Models\User;

class LoyaltyPricingService
{
    public function calculate(User $patient, Service $service): LoyaltyPriceResult
    {
        $account = $patient->loyaltyAccount;
        $tierDiscountPct = 0.0;
        $promoDiscountPct = 0.0;

        if ($account) {
            $tier = LoyaltyTier::where('tier', $account->tier)->first();
            $tierDiscountPct = (float) ($tier->discount_bonus_pct ?? 0);

            $rule = LoyaltyRule::active()->where('service_id', $service->id)->first();
            $promoDiscountPct = (float) ($rule->discount_pct ?? 0);
        }

        $originalPrice = (float) $service->price;
        $finalPrice = round($originalPrice * (1 - $tierDiscountPct / 100) * (1 - $promoDiscountPct / 100), 2);
        $discountAmount = round($originalPrice - $finalPrice, 2);

        $pointsToEarn = (int) (LoyaltyRule::where('service_id', $service->id)->value('points_earned') ?? 0);

        return new LoyaltyPriceResult(
            originalPrice: $originalPrice,
            discountPercent: $tierDiscountPct,
            promoDiscountPercent: $promoDiscountPct,
            discountAmount: $discountAmount,
            finalPrice: $finalPrice,
            pointsToEarn: $pointsToEarn,
            loyaltyTier: $account->tier ?? 'standard',
            pointsBalance: (int) ($account->points_balance ?? 0),
        );
    }
}
