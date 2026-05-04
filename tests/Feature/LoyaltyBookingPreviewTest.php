<?php

use App\Models\Doctor;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Services\LoyaltyPriceResult;
use App\Services\LoyaltyPricingService;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

// Helper: create a patient with an optional loyalty tier

function makePatientWithTier(string $tier = 'standard', float $discountPct = 0.0, int $points = 0): User
{
    $patient = User::factory()->create(['role' => 'patient']);
    $patient->assignRole('patient');

    // UserObserver auto-creates a 'standard' LoyaltyAccount on patient creation — update it
    $patient->loyaltyAccount()->update([
        'tier' => $tier,
        'points_balance' => $points,
    ]);

    LoyaltyTier::firstOrCreate(
        ['tier' => $tier],
        ['points_threshold' => 0, 'discount_bonus_pct' => $discountPct]
    );

    return $patient;
}

function makePreviewPayload(?int $doctorId = null, ?int $serviceId = null, ?int $slotId = null): array
{
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create(['price' => '20.00']);
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    return [
        'payload' => [
            'doctor_id' => $doctorId ?? $doctor->id,
            'service_id' => $serviceId ?? $service->id,
            'slot_id' => $slotId ?? $slot->id,
        ],
        'doctor' => $doctor,
        'service' => $service,
        'slot' => $slot,
    ];
}

// --- T8.2: silver-tier patient gets correct prices and points ---

it('returns correct prices and points for a silver-tier patient', function (): void {
    $patient = makePatientWithTier('silver', 10.0, 515);
    ['payload' => $payload, 'service' => $service] = makePreviewPayload();

    LoyaltyRule::factory()->create(['service_id' => $service->id, 'points_earned' => 18, 'discount_pct' => 0.00]);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments/preview', $payload)
        ->assertOk()
        ->assertJson([
            'original_price' => '20.00',
            'discount_percent' => 10.0,
            'discount_amount' => '2.00',
            'final_price' => '18.00',
            'points_to_earn' => 18,
            'loyalty_tier' => 'silver',
            'loyalty_points_balance' => 515,
        ]);
});

// --- T8.3: standard-tier patient gets zero discount ---

it('returns discount_percent 0 and full original_price for standard tier', function (): void {
    $patient = makePatientWithTier('standard', 0.0);
    ['payload' => $payload] = makePreviewPayload();

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments/preview', $payload)
        ->assertOk()
        ->assertJson([
            'original_price' => '20.00',
            'discount_percent' => 0.0,
            'final_price' => '20.00',
            'loyalty_tier' => 'standard',
        ]);
});

// --- T8.4: zero points_to_earn when no LoyaltyRule exists for service ---

it('returns points_to_earn 0 when no LoyaltyRule exists for service', function (): void {
    $patient = makePatientWithTier('standard', 0.0);
    ['payload' => $payload] = makePreviewPayload();

    // no LoyaltyRule created

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments/preview', $payload)
        ->assertOk()
        ->assertJsonPath('points_to_earn', 0);
});

// --- T8.5: unauthenticated request returns 401 ---

it('returns 401 when unauthenticated', function (): void {
    ['payload' => $payload] = makePreviewPayload();

    $this->postJson('/api/v1/appointments/preview', $payload)
        ->assertUnauthorized();
});

// --- T8.6: missing service_id returns 422 ---

it('returns 422 when service_id is missing', function (): void {
    $patient = makePatientWithTier('standard', 0.0);
    Sanctum::actingAs($patient);

    $doctor = Doctor::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $this->postJson('/api/v1/appointments/preview', [
        'doctor_id' => $doctor->id,
        'slot_id' => $slot->id,
        // service_id deliberately omitted
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['service_id']);
});

// --- T8.7: store() regression — produces correct discount_pct and final_price ---

it('store() produces correct discount_pct and final_price after refactor', function (): void {
    $patient = makePatientWithTier('silver', 10.0);
    $patient->forceFill(['email_verified_at' => now(), 'phone_verified_at' => now()])->save();

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create(['price' => '50.00']);
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertCreated()
        ->assertJsonPath('data.discount_pct', '10.00')
        ->assertJsonPath('data.final_price', '45.00');
});

// --- T8.8: LoyaltyPricingService unit test ---

it('LoyaltyPricingService::calculate returns correct result', function (): void {
    $patient = makePatientWithTier('silver', 10.0, 200);
    $service = Service::factory()->create(['price' => '100.00']);
    LoyaltyRule::factory()->create(['service_id' => $service->id, 'points_earned' => 50, 'discount_pct' => 0.00]);

    $result = app(LoyaltyPricingService::class)->calculate($patient, $service);

    expect($result)->toBeInstanceOf(LoyaltyPriceResult::class)
        ->and($result->originalPrice)->toBe(100.0)
        ->and($result->discountPercent)->toBe(10.0)
        ->and($result->promoDiscountPercent)->toBe(0.0)
        ->and($result->discountAmount)->toBe(10.0)
        ->and($result->finalPrice)->toBe(90.0)
        ->and($result->pointsToEarn)->toBe(50)
        ->and($result->loyaltyTier)->toBe('silver')
        ->and($result->pointsBalance)->toBe(200);
});

// --- 10.1: multiplicative stacking with both tier and promo discounts ---

it('applies tier and promo discount multiplicatively', function (): void {
    $patient = makePatientWithTier('silver', 5.0, 0);
    $service = Service::factory()->create(['price' => '100.00']);
    LoyaltyRule::factory()->create(['service_id' => $service->id, 'points_earned' => 0, 'discount_pct' => 2.50, 'is_active' => true]);

    $result = app(LoyaltyPricingService::class)->calculate($patient, $service);

    // 100 * (1 - 0.05) * (1 - 0.025) = 100 * 0.95 * 0.975 = 92.625 → 92.63
    expect($result->discountPercent)->toBe(5.0)
        ->and($result->promoDiscountPercent)->toBe(2.5)
        ->and($result->finalPrice)->toBe(92.63)
        ->and($result->discountAmount)->toBe(7.37);
});

// --- 10.2: promo not applied when patient has no LoyaltyAccount ---

it('does not apply promo discount when patient has no LoyaltyAccount', function (): void {
    $patient = User::factory()->create();
    $patient->loyaltyAccount()->delete();

    $service = Service::factory()->create(['price' => '100.00']);
    LoyaltyRule::factory()->create(['service_id' => $service->id, 'discount_pct' => 10.00, 'is_active' => true]);

    $result = app(LoyaltyPricingService::class)->calculate($patient, $service);

    expect($result->promoDiscountPercent)->toBe(0.0)
        ->and($result->finalPrice)->toBe(100.0);
});

// --- 10.3: expired rule not applied ---

it('does not apply promo discount from an expired rule', function (): void {
    $patient = makePatientWithTier('silver', 5.0, 0);
    $service = Service::factory()->create(['price' => '100.00']);

    // Rule expired: created 2 months ago with valid_months = 1
    $rule = LoyaltyRule::factory()->create([
        'service_id' => $service->id,
        'discount_pct' => 10.00,
        'is_active' => true,
        'valid_months' => 1,
        'points_earned' => 0,
    ]);
    $rule->forceFill(['created_at' => now()->subMonths(2)])->saveQuietly();

    $result = app(LoyaltyPricingService::class)->calculate($patient, $service);

    expect($result->promoDiscountPercent)->toBe(0.0);
});

// --- 10.4: is_active = false rule not applied ---

it('does not apply promo discount when rule is_active = false', function (): void {
    $patient = makePatientWithTier('silver', 5.0, 0);
    $service = Service::factory()->create(['price' => '100.00']);

    LoyaltyRule::factory()->create([
        'service_id' => $service->id,
        'discount_pct' => 10.00,
        'is_active' => false,
        'points_earned' => 0,
    ]);

    $result = app(LoyaltyPricingService::class)->calculate($patient, $service);

    expect($result->promoDiscountPercent)->toBe(0.0);
});
