<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $accountData = [
            ['email' => 'jonas.s@example.lt',   'points_balance' => 650, 'tier' => 'silver'],
            ['email' => 'egle.m@example.lt',     'points_balance' => 45,  'tier' => 'standard'],
            ['email' => 'ruta.j@example.lt',     'points_balance' => 1650, 'tier' => 'gold'],
            ['email' => 'andrius.b@example.lt',  'points_balance' => 15,  'tier' => 'standard'],
        ];

        foreach ($accountData as $data) {
            $patient = User::where('email', $data['email'])->first();

            if (! $patient) {
                continue;
            }

            $account = LoyaltyAccount::firstOrCreate(
                ['patient_id' => $patient->id],
                ['points_balance' => 0, 'tier' => 'standard']
            );

            // Create earn transactions for each completed appointment
            $completedAppointments = Appointment::where('patient_id', $patient->id)
                ->where('status', AppointmentStatus::Completed)
                ->get();

            foreach ($completedAppointments as $appointment) {
                $rule = LoyaltyRule::where('service_id', $appointment->service_id)->first();

                if ($rule) {
                    LoyaltyTransaction::firstOrCreate(
                        [
                            'loyalty_account_id' => $account->id,
                            'appointment_id'     => $appointment->id,
                        ],
                        [
                            'type'         => 'earn',
                            'points_delta' => $rule->points_earned,
                        ]
                    );
                }
            }

            // Set balance and tier explicitly per spec table
            $account->update([
                'points_balance' => $data['points_balance'],
                'tier'           => $data['tier'],
            ]);
        }
    }
}
