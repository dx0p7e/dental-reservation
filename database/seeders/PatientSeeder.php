<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['name' => 'Jonas Stankevičius',  'email' => 'jonas.s@example.lt',    'phone' => '+37060000001'],
            ['name' => 'Eglė Mackevičiūtė',   'email' => 'egle.m@example.lt',     'phone' => '+37060000002'],
            ['name' => 'Rūta Jankauskaite',    'email' => 'ruta.j@example.lt',     'phone' => '+37060000003'],
            ['name' => 'Andrius Butkus',       'email' => 'andrius.b@example.lt',  'phone' => '+37060000004'],
        ];

        foreach ($patients as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'role'              => 'patient',
                    'phone'             => $data['phone'],
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('patient');

            if (is_null($user->gdpr_consent_at)) {
                $user->gdpr_consent_at = now()->subDays(30);
                $user->save();
            }
        }
    }
}
