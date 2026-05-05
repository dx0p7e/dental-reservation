<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAppointmentRequest;
use Carbon\Carbon;
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

        $hasActiveAppointment = $user->appointments()
            ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])
            ->exists();

        if ($hasActiveAppointment) {
            return response()->json(['message' => 'Jau turite aktyvų vizitą. Prieš rezervuojant naują, atlikite esamą.'], 422);
        }

        $appointment = DB::transaction(function () use ($request) {
            $startSlot = ScheduleSlot::where('id', $request->slot_id)
                ->where('is_booked', false)
                ->lockForUpdate()
                ->firstOrFail();

            $service = Service::findOrFail($request->service_id);
            $endTime = Carbon::parse($startSlot->date->format('Y-m-d').' '.$startSlot->start_time)
                ->addMinutes($service->duration_minutes);

            $slotsToBook = ScheduleSlot::where('doctor_id', $startSlot->doctor_id)
                ->whereDate('date', $startSlot->date)
                ->where('start_time', '>=', $startSlot->start_time)
                ->where('start_time', '<', $endTime->format('H:i'))
                ->orderBy('start_time')
                ->lockForUpdate()
                ->get();

            $coveredMinutes = $slotsToBook->sum(
                fn (ScheduleSlot $s): int => (int) Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time)),
            );

            if ($slotsToBook->contains('is_booked', true) || $coveredMinutes < $service->duration_minutes) {
                abort(422, 'Nepakanka iš eilės einančių laisvų laiko tarpsnių šiai paslaugai.');
            }

            ScheduleSlot::whereIn('id', $slotsToBook->pluck('id'))->update(['is_booked' => true]);

            $result = $this->pricingService->calculate($request->user(), $service);

            return Appointment::create([
                'patient_id' => $request->user()->id,
                'doctor_id' => $request->doctor_id,
                'service_id' => $request->service_id,
                'slot_id' => $startSlot->id,
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

        DB::transaction(function () use ($appointment): void {
            if ($appointment->slot_id !== null) {
                $appointment->loadMissing(['slot', 'service']);
                $slot = $appointment->slot;
                $service = $appointment->service;

                if ($slot !== null && $service !== null) {
                    $endTime = Carbon::parse($slot->date->format('Y-m-d').' '.$slot->start_time)
                        ->addMinutes($service->duration_minutes);

                    ScheduleSlot::where('doctor_id', $slot->doctor_id)
                        ->whereDate('date', $slot->date)
                        ->where('start_time', '>=', $slot->start_time)
                        ->where('start_time', '<', $endTime->format('H:i'))
                        ->update(['is_booked' => false]);
                }
            }

            $appointment->update(['status' => AppointmentStatus::Cancelled]);
        });

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

        $appointment->loadMissing('service');
        $service = $appointment->service;
        assert($service !== null);

        DB::transaction(function () use ($appointment, $newSlot, $service): void {
            $newEndTime = Carbon::parse($newSlot->date->format('Y-m-d').' '.$newSlot->start_time)
                ->addMinutes($service->duration_minutes);

            $newSlots = ScheduleSlot::where('doctor_id', $newSlot->doctor_id)
                ->whereDate('date', $newSlot->date)
                ->where('start_time', '>=', $newSlot->start_time)
                ->where('start_time', '<', $newEndTime->format('H:i'))
                ->orderBy('start_time')
                ->lockForUpdate()
                ->get();

            $coveredMinutes = $newSlots->sum(
                fn (ScheduleSlot $s): int => (int) Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time)),
            );

            if ($newSlots->contains('is_booked', true) || $coveredMinutes < $service->duration_minutes) {
                abort(422, 'The selected slot is no longer available.');
            }

            $oldSlot = ScheduleSlot::where('id', $appointment->slot_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldEndTime = Carbon::parse($oldSlot->date->format('Y-m-d').' '.$oldSlot->start_time)
                ->addMinutes($service->duration_minutes);

            ScheduleSlot::where('doctor_id', $oldSlot->doctor_id)
                ->whereDate('date', $oldSlot->date)
                ->where('start_time', '>=', $oldSlot->start_time)
                ->where('start_time', '<', $oldEndTime->format('H:i'))
                ->update(['is_booked' => false]);

            ScheduleSlot::whereIn('id', $newSlots->pluck('id'))->update(['is_booked' => true]);
            $appointment->update(['slot_id' => $newSlots->first()->id, 'rescheduled_at' => now()]);
        });

        $appointment->load(['doctor.user', 'service', 'slot', 'patient']);
        $appointment->patient?->notify(new AppointmentRescheduledNotification($appointment));

        return new AppointmentResource($appointment);
    }
}
