<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\StoreAppointmentRequestRequest;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRequestedNotification;
use App\Notifications\AppointmentRescheduledNotification;
use App\Services\LoyaltyPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function __construct(private readonly LoyaltyPricingService $pricingService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $appointments = $request->user()
            ->appointments()
            ->with(['doctor.user', 'service', 'slot'])
            ->latest()
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function store(StoreAppointmentRequest $request): AppointmentResource|JsonResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail() || ! $user->hasVerifiedPhone()) {
            return response()->json(['message' => 'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant.'], 403);
        }

        $appointment = DB::transaction(function () use ($request) {
            $slot = ScheduleSlot::where('id', $request->slot_id)
                ->where('is_booked', false)
                ->lockForUpdate()
                ->firstOrFail();

            $slot->update(['is_booked' => true]);

            $service = Service::findOrFail($request->service_id);
            $result = $this->pricingService->calculate($request->user(), $service);

            return Appointment::create([
                'patient_id' => $request->user()->id,
                'doctor_id' => $request->doctor_id,
                'service_id' => $request->service_id,
                'slot_id' => $slot->id,
                'status' => $request->user()->smart_id_verified_at
                    ? AppointmentStatus::Confirmed
                    : AppointmentStatus::Pending,
                'discount_pct' => $result->discountPercent,
                'final_price' => $result->finalPrice,
            ]);
        });

        $appointment->load(['doctor.user', 'service', 'slot']);

        $appointment->loadMissing('patient');
        if ($appointment->status === AppointmentStatus::Confirmed) {
            $appointment->patient?->notify(new AppointmentConfirmedNotification($appointment));
        } else {
            $appointment->patient?->notify(new AppointmentBookedNotification($appointment));
        }

        return new AppointmentResource($appointment);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'slot_id' => ['required', 'integer', 'exists:schedule_slots,id'],
        ]);

        $service = Service::findOrFail($request->service_id);
        $result = $this->pricingService->calculate($request->user(), $service);

        return response()->json([
            'service_name' => $service->name,
            'original_price' => number_format($result->originalPrice, 2, '.', ''),
            'discount_percent' => $result->discountPercent,
            'promo_discount_percent' => $result->promoDiscountPercent,
            'discount_amount' => number_format($result->discountAmount, 2, '.', ''),
            'final_price' => number_format($result->finalPrice, 2, '.', ''),
            'points_to_earn' => $result->pointsToEarn,
            'loyalty_tier' => $result->loyaltyTier,
            'loyalty_points_balance' => $result->pointsBalance,
        ]);
    }

    public function show(Request $request, Appointment $appointment): AppointmentResource|JsonResponse
    {
        Gate::authorize('view', $appointment);

        return new AppointmentResource($appointment->load(['doctor.user', 'service', 'slot']));
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('delete', $appointment);

        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed])) {
            return response()->json(['message' => 'This appointment cannot be cancelled.'], 422);
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return response()->json(null, 204);
    }

    public function requestStore(StoreAppointmentRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail() || ! $user->hasVerifiedPhone()) {
            return response()->json(['message' => 'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant.'], 403);
        }

        $appointment = Appointment::create([
            'patient_id' => $request->user()->id,
            'service_id' => $request->service_id,
            'preferred_date' => $request->preferred_date,
            'notes' => $request->notes,
            'status' => AppointmentStatus::Pending,
            'slot_id' => null,
            'doctor_id' => null,
            'discount_pct' => 0,
            'final_price' => null,
        ]);

        $appointment->load(['service']);

        $appointment->loadMissing('patient');
        $appointment->patient?->notify(new AppointmentRequestedNotification($appointment));

        return (new AppointmentResource($appointment))->response()->setStatusCode(201);
    }

    public function reschedule(Request $request, Appointment $appointment): AppointmentResource|JsonResponse
    {
        Gate::authorize('reschedule', $appointment);

        if ($appointment->slot_id === null) {
            return response()->json(['message' => 'This appointment has no assigned slot and cannot be rescheduled.'], 422);
        }

        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed])) {
            return response()->json(['message' => 'This appointment cannot be rescheduled.'], 422);
        }

        if ($appointment->rescheduled_at !== null) {
            return response()->json(['message' => 'This appointment has already been rescheduled once and cannot be rescheduled again.'], 422);
        }

        $request->validate(['slot_id' => ['required', 'integer', 'exists:schedule_slots,id']]);

        $newSlot = ScheduleSlot::where('id', $request->slot_id)
            ->where('doctor_id', $appointment->doctor_id)
            ->firstOrFail();

        if ($newSlot->is_booked) {
            return response()->json(['message' => 'The selected slot is no longer available.'], 422);
        }

        DB::transaction(function () use ($appointment, $newSlot): void {
            $lockedNewSlot = ScheduleSlot::where('id', $newSlot->id)
                ->where('is_booked', false)
                ->lockForUpdate()
                ->firstOrFail();

            $oldSlot = ScheduleSlot::where('id', $appointment->slot_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldSlot->update(['is_booked' => false]);
            $lockedNewSlot->update(['is_booked' => true]);
            $appointment->update(['slot_id' => $lockedNewSlot->id, 'rescheduled_at' => now()]);
        });

        $appointment->load(['doctor.user', 'service', 'slot', 'patient']);
        $appointment->patient?->notify(new AppointmentRescheduledNotification($appointment));

        return new AppointmentResource($appointment);
    }
}
