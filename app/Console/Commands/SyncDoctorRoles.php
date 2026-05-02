<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SyncDoctorRoles extends Command
{
    protected $signature = 'doctor:sync-roles';

    protected $description = 'Ensure every user with role=doctor (enum) also has the Spatie doctor role assigned';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        $users = User::where('role', 'doctor')->get();
        $synced = 0;

        foreach ($users as $user) {
            if (! $user->hasRole('doctor')) {
                $user->assignRole('doctor');
                $synced++;
            }
        }

        $this->info("Synced {$synced} user(s). {$users->count()} doctor user(s) total.");

        return self::SUCCESS;
    }
}
