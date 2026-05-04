<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $discountPct = null;

        if (($user = auth('sanctum')->user()) instanceof User) {
            $tier = LoyaltyAccount::where('patient_id', $user->id)->value('tier');

            if ($tier !== null) {
                $raw = LoyaltyTier::where('tier', $tier)->value('discount_bonus_pct');
                $discountPct = $raw !== null ? (float) $raw : null;
            }
        }

        $request->attributes->set('loyalty_discount_pct', $discountPct);

        return ServiceResource::collection(
            Service::with(['loyaltyRule' => fn ($q) => $q->active()])->get()
        );
    }
}
