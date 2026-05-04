<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', '123')),
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
