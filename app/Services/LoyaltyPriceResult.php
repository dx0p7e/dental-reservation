<?php

namespace App\Services;

readonly class LoyaltyPriceResult
{
    public function __construct(
        public float $originalPrice,
        public float $discountPercent,
        public float $promoDiscountPercent,
        public float $discountAmount,
        public float $finalPrice,
        public int $pointsToEarn,
        public string $loyaltyTier,
        public int $pointsBalance,
    ) {}
}
