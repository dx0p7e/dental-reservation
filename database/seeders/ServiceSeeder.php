<?php

namespace Database\Seeders;

use App\Models\LoyaltyRule;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Dantų valymas (higiena)',       'duration_minutes' => 60, 'price' => 45.00,  'complexity' => 'simple',  'description' => 'Profesionalus dantų valymas ir higienos procedūra.',                'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Dantų apžiūra ir rentgenas',    'duration_minutes' => 30, 'price' => 35.00,  'complexity' => 'simple',  'description' => 'Išsami dantų apžiūra su skaitmeniniu rentgenu.',                  'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Dantų plombavimas (kompozitas)', 'duration_minutes' => 45, 'price' => 80.00,  'complexity' => 'simple',  'description' => 'Dantų plombavimas kompozitine medžiaga.',                           'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Danties šalinimas',              'duration_minutes' => 30, 'price' => 60.00,  'complexity' => 'simple',  'description' => 'Paprasta danties šalinimo procedūra.',                             'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Šaknies kanalo gydymas',         'duration_minutes' => 90, 'price' => 180.00, 'complexity' => 'complex', 'description' => 'Endodontinis šaknies kanalo gydymas.',                             'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Dantų balinimas',                'duration_minutes' => 60, 'price' => 120.00, 'complexity' => 'simple',  'description' => 'Profesionalus dantų balinimas klinikoje.',                         'promo_pct' => 5.00, 'valid_months' => 3],
            ['name' => 'Dantų karūnėlė',                'duration_minutes' => 60, 'price' => 250.00, 'complexity' => 'complex', 'description' => 'Porceliano arba keramikos karūnėlės restauracija.',               'promo_pct' => 0.00, 'valid_months' => null],
            ['name' => 'Vaikų dantų apžiūra',           'duration_minutes' => 30, 'price' => 25.00,  'complexity' => 'simple',  'description' => 'Profilaktinė dantų apžiūra vaikams.',                              'promo_pct' => 0.00, 'valid_months' => null],
        ];

        foreach ($services as $data) {
            $service = Service::firstOrCreate(
                ['name' => $data['name']],
                [
                    'duration_minutes' => $data['duration_minutes'],
                    'price'            => $data['price'],
                    'complexity'       => $data['complexity'],
                    'description'      => $data['description'],
                ]
            );

            LoyaltyRule::updateOrCreate(
                ['service_id' => $service->id],
                [
                    'points_earned' => (int) round($data['price']),
                    'discount_pct'  => $data['promo_pct'],
                    'valid_months'  => $data['valid_months'],
                    'is_active'     => true,
                ]
            );
        }
    }
}
