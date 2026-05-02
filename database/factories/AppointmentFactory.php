<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'   => User::factory(),
            'doctor_id'    => Doctor::factory(),
            'service_id'   => Service::factory(),
            'slot_id'      => ScheduleSlot::factory(),
            'status'       => AppointmentStatus::Pending,
            'notes'        => null,
            'doctor_notes' => null,
        ];
    }
}
