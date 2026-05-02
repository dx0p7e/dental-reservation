<?php

namespace Database\Seeders;

use App\Models\PatientReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'email'  => 'jonas.s@example.lt',
                'rating' => 5,
                'title'  => 'Puiki klinika!',
                'body'   => 'Labai patenkinta apsilankymu. Gydytoja buvo draugiška ir profesionali, procedūra praėjo sklandžiai. Drąsiai rekomenduoju šią kliniką visiems, kurie ieško patikimos odontologijos paslaugų.',
            ],
            [
                'email'  => 'egle.m@example.lt',
                'rating' => 5,
                'title'  => 'Rekomenduoju visiems',
                'body'   => 'Dantų balinimo procedūra buvo atlikta profesionaliai ir greitai. Rezultatu esu labai patenkinta – dantys atrodo puikiai. Personalas malonus ir paslaugus, laukiamasis švarus ir jaukus.',
            ],
            [
                'email'  => 'ruta.j@example.lt',
                'rating' => 4,
                'title'  => 'Gera patirtis',
                'body'   => 'Šaknies kanalo gydymas vyko be didelio skausmo, gydytojas aiškiai paaiškino kiekvieną žingsnį. Laukimo laikas buvo šiek tiek ilgesnis nei tikėtasi, tačiau bendra patirtis teigiama.',
            ],
            [
                'email'  => 'andrius.b@example.lt',
                'rating' => 5,
                'title'  => 'Profesionalūs gydytojai',
                'body'   => 'Vaiko pirmasis apsilankymas pas odontologą praėjo labai gerai – gydytoja buvo kantri ir draugiška, vaikas nesibijojo. Tikrai grįšime ir rekomenduosime draugams.',
            ],
        ];

        foreach ($reviews as $data) {
            $patient = User::where('email', $data['email'])->first();

            if (! $patient) {
                continue;
            }

            PatientReview::firstOrCreate(
                ['patient_id' => $patient->id],
                [
                    'rating'       => $data['rating'],
                    'title'        => $data['title'],
                    'body'         => $data['body'],
                    'is_published' => true,
                ]
            );
        }
    }
}

