<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('awards points and creates a transaction when appointment is completed', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 10]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account->points_balance)->toBe(10);

    $tx = LoyaltyTransaction::where('appointment_id', $appointment->id)->first();
    expect($tx)->not->toBeNull();
    expect($tx->points_delta)->toBe(10);
    expect($tx->type)->toBe('earn');
    expect($tx->loyalty_account_id)->toBe($account->id);
});

it('does not award points when no loyalty rule exists for the service', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account->points_balance)->toBe(0);
    expect(LoyaltyTransaction::where('appointment_id', $appointment->id)->count())->toBe(0);
});

it('does not create a duplicate transaction when already-completed appointment is re-saved', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Completed]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 10]);

    // Re-save without changing status — wasChanged('status') will be false
    $appointment->update(['notes' => 'follow-up']);

    expect(LoyaltyTransaction::where('appointment_id', $appointment->id)->count())->toBe(0);
});

it('does not award points when status changes to a non-Completed value', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 10]);

    $appointment->update(['status' => AppointmentStatus::Confirmed]);

    expect(LoyaltyTransaction::where('appointment_id', $appointment->id)->count())->toBe(0);
    expect(LoyaltyAccount::where('patient_id', $appointment->patient_id)->value('points_balance'))->toBe(0);
});

it('creates a loyalty account via firstOrCreate if it does not exist when points are awarded', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 20]);

    // Remove the account auto-created by UserObserver so we can test firstOrCreate in the AppointmentObserver
    LoyaltyAccount::where('patient_id', $appointment->patient_id)->delete();
    expect(LoyaltyAccount::where('patient_id', $appointment->patient_id)->exists())->toBeFalse();

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account)->not->toBeNull();
    expect($account->points_balance)->toBe(20);
});

it('upgrades tier to silver when balance crosses the silver threshold after earning', function (): void {
    LoyaltyTier::create(['tier' => 'standard', 'points_threshold' => 0, 'discount_bonus_pct' => 0]);
    LoyaltyTier::create(['tier' => 'silver', 'points_threshold' => 100, 'discount_bonus_pct' => 5]);

    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 50]);
    LoyaltyAccount::where('patient_id', $appointment->patient_id)->update(['points_balance' => 60]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account->tier)->toBe('silver');
    expect($account->points_balance)->toBe(110);
});

it('does not change tier when balance does not cross the next threshold', function (): void {
    LoyaltyTier::create(['tier' => 'standard', 'points_threshold' => 0, 'discount_bonus_pct' => 0]);
    LoyaltyTier::create(['tier' => 'silver', 'points_threshold' => 100, 'discount_bonus_pct' => 5]);

    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 10]);
    LoyaltyAccount::where('patient_id', $appointment->patient_id)->update(['points_balance' => 30]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account->tier)->toBe('standard');
    expect($account->points_balance)->toBe(40);
});

it('skips tier recalculation gracefully when the loyalty tiers table is empty', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);
    LoyaltyRule::factory()->create(['service_id' => $appointment->service_id, 'points_earned' => 10]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    $account = LoyaltyAccount::where('patient_id', $appointment->patient_id)->first();
    expect($account->tier)->toBe('standard');
    expect($account->points_balance)->toBe(10);
});
