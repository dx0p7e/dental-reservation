<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Doctor;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->setLoyaltyDiscount($request);

        return DoctorResource::collection(
            Doctor::where('is_active', true)
                ->with(['user', 'services' => fn ($q) => $q->with(['loyaltyRule' => fn ($q) => $q->active()])])
                ->get()
        );
    }

    public function show(Doctor $doctor, Request $request): DoctorResource
    {
        $this->setLoyaltyDiscount($request);
        $doctor->load(['user', 'services' => fn ($q) => $q->with(['loyaltyRule' => fn ($q) => $q->active()])]);

        return new DoctorResource($doctor);
    }

    public function services(Doctor $doctor, Request $request): AnonymousResourceCollection
    {
        $this->setLoyaltyDiscount($request);

        return ServiceResource::collection(
            $doctor->services()->with(['loyaltyRule' => fn ($q) => $q->active()])->get()
        );
    }

    private function setLoyaltyDiscount(Request $request): void
    {
        $discountPct = null;

        if ($user = auth('sanctum')->user()) {
            $tier = LoyaltyAccount::where('patient_id', $user->id)->value('tier');

            if ($tier !== null) {
                $raw = LoyaltyTier::where('tier', $tier)->value('discount_bonus_pct');
                $discountPct = $raw !== null ? (float) $raw : null;
            }
        }

        $request->attributes->set('loyalty_discount_pct', $discountPct);
    }
}
