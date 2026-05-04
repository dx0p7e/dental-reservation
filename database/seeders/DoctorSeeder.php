<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'name' => 'Dr. Marta Kazlauskienė',
                'email' => 'marta.kazlauskiene@klinika.lt',
                'specialization' => 'Bendroji odontologija',
                'bio' => 'Dr. Kazlauskienė turi daugiau nei 10 metų patirties bendrojoje odontologijoje, specializuojasi prevencinėje priežiūroje ir šeimos dantų sveikatoje.',
                'services' => ['Dantų valymas (higiena)', 'Dantų apžiūra ir rentgenas', 'Dantų plombavimas (kompozitas)', 'Danties šalinimas', 'Vaikų dantų apžiūra'],
            ],
            [
                'name' => 'Dr. Tomas Petrauskas',
                'email' => 'tomas.petrauskas@klinika.lt',
                'specialization' => 'Ortodontija ir endodontija',
                'bio' => 'Dr. Petrauskas specializuojasi sudėtingose odontologinėse procedūrose, įskaitant šaknies kanalo gydymą ir ortodontinius darbus.',
                'services' => ['Dantų apžiūra ir rentgenas', 'Šaknies kanalo gydymas', 'Danties šalinimas', 'Dantų plombavimas (kompozitas)'],
            ],
            [
                'name' => 'Dr. Aistė Rimkutė',
                'email' => 'aiste.rimkute@klinika.lt',
                'specialization' => 'Estetinė odontologija',
                'bio' => 'Dr. Rimkutė yra estetinės odontologijos specialistė, sutelkusi dėmesį į dantų balinimą, karūnėles ir estetinę restauraciją.',
                'services' => ['Dantų balinimas', 'Dantų karūnėlė', 'Dantų valymas (higiena)', 'Dantų apžiūra ir rentgenas'],
            ],
        ];

        foreach ($doctors as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('doctor');

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialization' => $data['specialization'],
                    'bio' => $data['bio'],
                    'is_active' => true,
                ]
            );

            $serviceIds = Service::whereIn('name', $data['services'])->pluck('id')->toArray();
            $doctor->services()->syncWithoutDetaching($serviceIds);
        }
    }
}
