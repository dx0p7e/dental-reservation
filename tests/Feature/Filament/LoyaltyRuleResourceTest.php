<?php

use App\Filament\Resources\LoyaltyRules\Pages\CreateLoyaltyRule;
use App\Filament\Resources\LoyaltyRules\Pages\EditLoyaltyRule;
use App\Filament\Resources\LoyaltyRules\Pages\ListLoyaltyRules;
use App\Models\LoyaltyRule;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
});

test('list page renders', function (): void {
    Livewire::test(ListLoyaltyRules::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateLoyaltyRule::class)
        ->assertSuccessful();
});

test('can create a loyalty rule', function (): void {
    $service = Service::factory()->create();

    Livewire::test(CreateLoyaltyRule::class)
        ->fillForm([
            'service_id' => $service->id,
            'points_earned' => 10,
            'discount_pct' => '5.00',
            'valid_months' => 12,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(LoyaltyRule::query()->where('service_id', $service->id)->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $rule = LoyaltyRule::factory()->create();

    Livewire::test(EditLoyaltyRule::class, ['record' => $rule->id])
        ->assertSuccessful();
});

test('can edit a loyalty rule', function (): void {
    $rule = LoyaltyRule::factory()->create();

    Livewire::test(EditLoyaltyRule::class, ['record' => $rule->id])
        ->fillForm(['points_earned' => 25])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($rule->fresh()->points_earned)->toBe(25);
});
