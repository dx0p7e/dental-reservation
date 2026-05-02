<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorSchedule>
 */
class DoctorScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ];
    }
}
