<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Seeder;

class DoctorScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();

        foreach ($doctors as $doctor) {
            foreach (range(0, 4) as $dayOfWeek) {
                DoctorSchedule::firstOrCreate(
                    [
                        'doctor_id'   => $doctor->id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    [
                        'start_time'            => '09:00',
                        'end_time'              => '17:00',
                        'slot_duration_minutes' => 30,
                        'is_active'             => true,
                    ]
                );
            }
        }
    }
}
