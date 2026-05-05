<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTier;
use Illuminate\Http\JsonResponse;

class LoyaltyTierController extends Controller
{
    public function index(): JsonResponse
    {
        $tiers = LoyaltyTier::orderBy('points_threshold')->get(['tier', 'points_threshold', 'discount_bonus_pct', 'color']);

        return response()->json(['data' => $tiers]);
    }
}
