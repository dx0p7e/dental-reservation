<?php

namespace App\Observers;

use App\Models\Doctor;
use Spatie\Permission\Models\Role;

class DoctorObserver
{
    public function created(Doctor $doctor): void
    {
        $this->syncUserRole($doctor);
    }

    public function updated(Doctor $doctor): void
    {
        if ($doctor->wasChanged('user_id')) {
            $this->syncUserRole($doctor);
        }
    }

    private function syncUserRole(Doctor $doctor): void
    {
        $user = $doctor->user;

        if ($user === null) {
            return;
        }

        $user->role = 'doctor';
        $user->saveQuietly();

        $role = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $user->assignRole($role);
    }
}
