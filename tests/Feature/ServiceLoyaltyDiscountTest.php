<?php

use App\Models\Doctor;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\Service;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

    LoyaltyTier::factory()->create(['tier' => 'standard', 'discount_bonus_pct' => 0.0, 'points_threshold' => 0]);
    LoyaltyTier::factory()->create(['tier' => 'silver',   'discount_bonus_pct' => 5.0, 'points_threshold' => 100]);
    LoyaltyTier::factory()->create(['tier' => 'gold',     'discount_bonus_pct' => 10.0, 'points_threshold' => 300]);

    Service::factory()->create();
});

test('unauthenticated request returns loyalty_discount_pct as null', function (): void {
    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.loyalty_discount_pct', null);
});

test('authenticated patient with Silver tier receives 5.0 discount', function (): void {
    $patient = User::factory()->create();
    $patient->loyaltyAccount->update(['tier' => 'silver']);
    $token = $patient->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/services')
        ->assertOk();

    $response->assertJsonPath('data.0.loyalty_discount_pct', 5);
});

test('authenticated patient with Gold tier receives 10.0 discount', function (): void {
    $patient = User::factory()->create();
    $patient->loyaltyAccount->update(['tier' => 'gold']);
    $token = $patient->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.loyalty_discount_pct', 10);
});

test('authenticated patient with Standard tier receives 0.0 discount', function (): void {
    $patient = User::factory()->create();
    $patient->loyaltyAccount->update(['tier' => 'standard']);
    $token = $patient->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.loyalty_discount_pct', 0);
});

test('authenticated patient with no LoyaltyAccount receives null', function (): void {
    $patient = User::factory()->create();
    $patient->loyaltyAccount()->delete();
    $token = $patient->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.loyalty_discount_pct', null);
});

test('service with active promo rule returns promo_discount_pct', function (): void {
    $service = Service::all()->first();
    LoyaltyRule::factory()->create(['service_id' => $service->id, 'discount_pct' => 2.50, 'is_active' => true]);

    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.promo_discount_pct', 2.5);
});

test('service with no active promo rule returns null for promo_discount_pct', function (): void {
    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonPath('data.0.promo_discount_pct', null);
});

test('doctor services endpoint returns promo_discount_pct', function (): void {
    $service = Service::all()->first();
    LoyaltyRule::factory()->create(['service_id' => $service->id, 'discount_pct' => 3.00, 'is_active' => true]);

    $doctor = Doctor::factory()->create(['is_active' => true]);
    $doctor->services()->attach($service->id);

    $this->getJson("/api/v1/doctors/{$doctor->id}/services")
        ->assertOk()
        ->assertJsonPath('data.0.promo_discount_pct', 3);
});
