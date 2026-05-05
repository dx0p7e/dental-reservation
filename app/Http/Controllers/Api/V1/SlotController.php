<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SlotResource;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class SlotController extends Controller
{
    public function index(Request $request, Doctor $doctor): AnonymousResourceCollection
    {
        $query = $doctor->slots()
            ->when($request->date, fn ($q) => $q->whereDate('date', $request->date))
            ->orderBy('date')
            ->orderBy('start_time');

        if ($request->filled('service_id')) {
            $service = Service::findOrFail($request->integer('service_id'));
            $durationMinutes = $service->duration_minutes;

            $allSlots = $query->get();
            $byDate = $allSlots->groupBy(fn (ScheduleSlot $slot): string => $slot->date->format('Y-m-d'));

            $available = $allSlots->filter(
                fn (ScheduleSlot $slot): bool => ! $slot->is_booked
                    && $this->hasConsecutiveFreeSlots($slot, $byDate[$slot->date->format('Y-m-d')], $durationMinutes),
            );

            return SlotResource::collection($available->values());
        }

        return SlotResource::collection($query->where('is_booked', false)->get());
    }

    /**
     * @param  Collection<int, ScheduleSlot>  $dateSlots  ordered by start_time ascending
     */
    private function hasConsecutiveFreeSlots(ScheduleSlot $startSlot, Collection $dateSlots, int $durationMinutes): bool
    {
        $covered = 0;
        $expectedStart = Carbon::parse($startSlot->start_time)->format('H:i:s');

        foreach ($dateSlots as $slot) {
            $slotStart = Carbon::parse($slot->start_time)->format('H:i:s');

            if ($slotStart < $expectedStart) {
                continue;
            }

            if ($slotStart !== $expectedStart || $slot->is_booked) {
                break;
            }

            $covered += Carbon::parse($slot->start_time)->diffInMinutes(Carbon::parse($slot->end_time));
            $expectedStart = Carbon::parse($slot->end_time)->format('H:i:s');

            if ($covered >= $durationMinutes) {
                return true;
            }
        }

        return false;
    }
}
