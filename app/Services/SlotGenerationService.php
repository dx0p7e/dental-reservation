<?php

namespace App\Services;

use App\Models\DoctorSchedule;
use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SlotGenerationService
{
    /**
     * Generate ScheduleSlot rows for a single DoctorSchedule over a date range.
     *
     * @return array{created: int, skipped: int}
     */
    public function generateForSchedule(DoctorSchedule $schedule, CarbonInterface $from, int $days): array
    {
        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);

            if ($date->isoWeekday() !== $schedule->day_of_week) {
                continue;
            }

            $slotStart = Carbon::parse($date->toDateString().' '.$schedule->start_time);
            $slotEnd = Carbon::parse($date->toDateString().' '.$schedule->end_time);
            $duration = $schedule->slot_duration_minutes;

            while ($slotStart->copy()->addMinutes($duration)->lte($slotEnd)) {
                $end = $slotStart->copy()->addMinutes($duration);

                $slot = ScheduleSlot::firstOrCreate(
                    [
                        'doctor_id' => $schedule->doctor_id,
                        'date' => $date->toDateString(),
                        'start_time' => $slotStart->format('H:i:s'),
                    ],
                    [
                        'end_time' => $end->format('H:i:s'),
                        'slot_type' => 'self',
                        'is_booked' => false,
                    ]
                );

                if ($slot->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }

                $slotStart = $end;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
