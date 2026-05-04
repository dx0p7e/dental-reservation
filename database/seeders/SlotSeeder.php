<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();
        $start = Carbon::today()->subDays(1);
        $end = Carbon::today()->addDays(60);
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $slotStart = $current->copy()->setHour(9)->setMinute(0)->setSecond(0);
                $dayEnd = $current->copy()->setHour(17)->setMinute(0)->setSecond(0);

                while ($slotStart->lt($dayEnd)) {
                    $slotEndTime = $slotStart->copy()->addMinutes(30);

                    foreach ($doctors as $doctor) {
                        ScheduleSlot::firstOrCreate(
                            [
                                'doctor_id' => $doctor->id,
                                'date' => $current->toDateString(),
                                'start_time' => $slotStart->format('H:i:s'),
                            ],
                            [
                                'end_time' => $slotEndTime->format('H:i:s'),
                                'slot_type' => 'self',
                                'is_booked' => false,
                            ]
                        );
                    }

                    $slotStart->addMinutes(30);
                }
            }

            $current->addDay();
        }
    }
}
