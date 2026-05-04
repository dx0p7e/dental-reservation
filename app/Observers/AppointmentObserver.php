<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentNoShowNotification;
use Illuminate\Support\Facades\DB;

class AppointmentObserver
{
    public function updated(Appointment $appointment): void
    {
        if (! $appointment->wasChanged('status')) {
            return;
        }

        $appointment->loadMissing('patient');

        match ($appointment->status) {
            AppointmentStatus::Confirmed => $appointment->patient?->notify(new AppointmentConfirmedNotification($appointment)),
            AppointmentStatus::Cancelled => $appointment->patient?->notify(new AppointmentCancelledNotification($appointment)),
            AppointmentStatus::NoShow => $appointment->patient?->notify(new AppointmentNoShowNotification($appointment)),
            AppointmentStatus::Completed => $this->handleCompleted($appointment),
            default => null,
        };
    }

    private function handleCompleted(Appointment $appointment): void
    {
        $appointment->patient?->notify(new AppointmentCompletedNotification($appointment));

        $rule = LoyaltyRule::where('service_id', $appointment->service_id)->first();

        if ($rule === null) {
            return;
        }

        DB::transaction(function () use ($appointment, $rule): void {
            $account = LoyaltyAccount::firstOrCreate(
                ['patient_id' => $appointment->patient_id],
                ['points_balance' => 0, 'tier' => 'standard'],
            );

            $newBalance = $account->points_balance + $rule->points_earned;

            LoyaltyTransaction::create([
                'loyalty_account_id' => $account->id,
                'appointment_id' => $appointment->id,
                'points_delta' => $rule->points_earned,
                'type' => 'earn',
            ]);

            $account->increment('points_balance', $rule->points_earned);

            $bestTier = LoyaltyTier::where('points_threshold', '<=', $newBalance)
                ->orderByDesc('points_threshold')
                ->value('tier');

            if ($bestTier !== null && $bestTier !== $account->tier) {
                $account->update(['tier' => $bestTier]);
            }
        });
    }
}
