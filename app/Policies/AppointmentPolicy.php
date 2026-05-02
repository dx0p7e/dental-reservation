<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id;
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id;
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
