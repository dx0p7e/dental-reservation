#!/usr/bin/env python3
content = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Enums\\AppointmentStatus;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Api\\V1\\StoreAppointmentRequest;
use App\\Http\\Requests\\Api\\V1\\StoreAppointmentRequestRequest;
use App\\Http\\Resources\\Api\\V1\\AppointmentResource;
use App\\Models\\Appointment;
use App\\Models\\LoyaltyTier;
use App\\Models\\ScheduleSlot;
use App\\Models\\Service;
use App\\Notifications\\AppointmentBookedNotification;
use App\\Notifications\\AppointmentRequestedNotification;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Gate;

class AppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $appointments = $request->user()
            ->appointments()
            ->with([\'doctor.user\', \'service\', \'slot\'])
            ->latest()
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function store(StoreAppointmentRequest $request): AppointmentResource|JsonResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail() || ! $user->hasVerifiedPhone()) {
            return response()->json([\'message\' => \'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant.\'], 403);
        }

        $appointment = DB::transaction(function () use ($request) {
            $slot = ScheduleSlot::where(\'id\', $request->slot_id)
                ->where(\'is_booked\', false)
                ->lockForUpdate()
                ->firstOrFail();

            $slot->update([\'is_booked\' => true]);

            $account = $request->user()->loyaltyAccount;
            $discountPct = 0;
            if ($account) {
                $tier = LoyaltyTier::where(\'tier\', $account->tier)->first();
                $discountPct = $tier?->discount_bonus_pct ?? 0;
            }

            $service = Service::find($request->service_id);
            $finalPrice = round($service->price * (1 - $discountPct / 100), 2);

            return Appointment::create([
                \'patient_id\'  => $request->user()->id,
                \'doctor_id\'   => $request->doctor_id,
                \'service_id\'  => $request->service_id,
                \'slot_id\'     => $slot->id,
                \'status\'      => AppointmentStatus::Pending,
                \'discount_pct\' => $discountPct,
                \'final_price\'  => $finalPrice,
            ]);
        });

        $appointment->load([\'doctor.user\', \'service\', \'slot\']);

        $appointment->loadMissing(\'patient\');
        $appointment->patient->notify(new AppointmentBookedNotification($appointment));

        return new AppointmentResource($appointment);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize(\'delete\', $appointment);

        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed])) {
            return response()->json([\'message\' => \'This appointment cannot be cancelled.\'], 422);
        }

        $appointment->update([\'status\' => AppointmentStatus::Cancelled]);

        return response()->json(null, 204);
    }

    public function requestStore(StoreAppointmentRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail() || ! $user->hasVerifiedPhone()) {
            return response()->json([\'message\' => \'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant.\'], 403);
        }

        $appointment = Appointment::create([
            \'patient_id\'     => $request->user()->id,
            \'service_id\'     => $request->service_id,
            \'preferred_date\' => $request->preferred_date,
            \'notes\'          => $request->notes,
            \'status\'         => AppointmentStatus::Pending,
            \'slot_id\'        => null,
            \'doctor_id\'      => null,
            \'discount_pct\'   => 0,
            \'final_price\'    => null,
        ]);

        $appointment->load([\'service\']);

        $appointment->loadMissing(\'patient\');
        $appointment->patient->notify(new AppointmentRequestedNotification($appointment));

        return (new AppointmentResource($appointment))->response()->setStatusCode(201);
    }
}
'''

with open('app/Http/Controllers/Api/V1/AppointmentController.php', 'w') as f:
    f.write(content)

print("Done! File written.")
