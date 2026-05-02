<?php

use App\Models\Appointment;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Mail::fake();
});

test('loyalty endpoint returns next_tier and points_to_next_tier when below the highest tier', function (): void {
    LoyaltyTier::factory()->create(['tier' => 'standard', 'points_threshold' => 0]);
    LoyaltyTier::factory()->create(['tier' => 'silver', 'points_threshold' => 100]);
    LoyaltyTier::factory()->create(['tier' => 'gold', 'points_threshold' => 300]);

    $patient = User::factory()->create();
    $patient->loyaltyAccount->update(['points_balance' => 50]);
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/loyalty')
        ->assertOk()
        ->assertJson(['data' => [
            'next_tier' => 'silver',
            'points_to_next_tier' => 50,
        ]]);
});

test('loyalty endpoint returns null next-tier fields when patient is at the highest tier', function (): void {
    LoyaltyTier::factory()->create(['tier' => 'standard', 'points_threshold' => 0]);
    LoyaltyTier::factory()->create(['tier' => 'silver', 'points_threshold' => 100]);
    LoyaltyTier::factory()->create(['tier' => 'gold', 'points_threshold' => 300]);

    $patient = User::factory()->create();
    $patient->loyaltyAccount->update(['points_balance' => 300]);
    Sanctum::actingAs($patient);

    $response = $this->getJson('/api/v1/loyalty')->assertOk();
    expect($response->json('data.next_tier'))->toBeNull();
    expect($response->json('data.points_to_next_tier'))->toBeNull();
});

test('loyalty endpoint returns null next-tier fields when no LoyaltyTier rows exist', function (): void {
    $patient = User::factory()->create();
    Sanctum::actingAs($patient);

    $response = $this->getJson('/api/v1/loyalty')->assertOk();
    expect($response->json('data.next_tier'))->toBeNull();
    expect($response->json('data.points_to_next_tier'))->toBeNull();
});

test('loyalty endpoint returns transactions array with correct shape', function (): void {
    $patient = User::factory()->create();
    $account = $patient->loyaltyAccount;

    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);
    LoyaltyTransaction::create([
        'loyalty_account_id' => $account->id,
        'appointment_id' => $appointment->id,
        'points_delta' => 25,
        'type' => 'earn',
    ]);

    Sanctum::actingAs($patient);

    $response = $this->getJson('/api/v1/loyalty')->assertOk();
    $transactions = $response->json('data.transactions');

    expect($transactions)->toHaveCount(1);
    expect($transactions[0])->toMatchArray([
        'type' => 'earn',
        'points_delta' => 25,
        'service_name' => $appointment->service->name,
    ]);
    expect($transactions[0])->toHaveKeys(['id', 'type', 'points_delta', 'service_name', 'created_at']);
});

test('loyalty endpoint returns service_name null for transactions without an appointment', function (): void {
    $patient = User::factory()->create();
    $account = $patient->loyaltyAccount;

    LoyaltyTransaction::create([
        'loyalty_account_id' => $account->id,
        'appointment_id' => null,
        'points_delta' => 10,
        'type' => 'earn',
    ]);

    Sanctum::actingAs($patient);

    $response = $this->getJson('/api/v1/loyalty')->assertOk();
    $transactions = $response->json('data.transactions');

    expect($transactions)->toHaveCount(1);
    expect($transactions[0]['service_name'])->toBeNull();
});
