<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LoyaltyResource;
use App\Models\LoyaltyAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function show(Request $request): LoyaltyResource|JsonResponse
    {
        $account = $request->user()->loyaltyAccount()->with([
            'transactions' => fn ($q) => $q->latest()->with('appointment.service'),
        ])->first();

        if ($account === null) {
            $account = new LoyaltyAccount(['points_balance' => 0, 'tier' => 'standard']);
            $account->setRelation('transactions', collect([]));
        }

        return new LoyaltyResource($account);
    }
}
