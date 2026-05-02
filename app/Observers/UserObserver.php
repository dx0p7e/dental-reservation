<?php

namespace App\Observers;

use App\Models\LoyaltyAccount;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->role === 'patient') {
            LoyaltyAccount::create([
                'patient_id'     => $user->id,
                'points_balance' => 0,
                'tier'           => 'standard',
            ]);
        }

        if (in_array($user->role, ['doctor', 'admin'], true)) {
            $role = Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }
    }
}
