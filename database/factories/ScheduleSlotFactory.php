<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\ScheduleSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSlot>
 */
class ScheduleSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '09:30',
            'slot_type' => 'self',
            'is_booked' => false,
        ];
    }
}
