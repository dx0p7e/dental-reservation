<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

test('loyalty endpoint returns balance and tier', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    // Observer already created the loyalty account; update it
    $patient->loyaltyAccount->update(['points_balance' => 120, 'tier' => 'silver']);
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/loyalty')
        ->assertOk()
        ->assertJson(['data' => ['points_balance' => 120, 'tier' => 'silver']]);
});

test('loyalty endpoint returns defaults when no account exists', function (): void {
    // Use a non-patient role so observer does not create a loyalty account
    $patient = User::factory()->create(['role' => 'doctor']);
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $this->getJson('/api/v1/loyalty')
        ->assertOk()
        ->assertJson(['data' => ['points_balance' => 0, 'tier' => 'standard']]);
});

test('loyalty fallback account response includes an empty transactions array', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('patient');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/loyalty')->assertOk();

    expect($response->json('data.transactions'))->toBeArray()->toBeEmpty();
});

test('unauthenticated request returns 401', function (): void {
    $this->getJson('/api/v1/loyalty')
        ->assertUnauthorized();
});

