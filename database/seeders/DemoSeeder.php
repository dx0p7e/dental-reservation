<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LoyaltyTierSeeder::class,
            ServiceSeeder::class,
            DoctorSeeder::class,
            PatientSeeder::class,
            DoctorScheduleSeeder::class,
            SlotSeeder::class,
            AppointmentSeeder::class,
            LoyaltySeeder::class,
            ReviewSeeder::class,
        ]);

        // Ensure demo admin user exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@klinika.lt'],
            [
                'name' => 'Klinikos Administratorius',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        if (is_null($admin->gdpr_consent_at)) {
            $admin->gdpr_consent_at = now();
            $admin->save();
        }
    }
}
