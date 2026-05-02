<?php

use App\Filament\Resources\LoyaltyTiers\Pages\CreateLoyaltyTier;
use App\Filament\Resources\LoyaltyTiers\Pages\EditLoyaltyTier;
use App\Filament\Resources\LoyaltyTiers\Pages\ListLoyaltyTiers;
use App\Models\LoyaltyTier;
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
    Livewire::test(ListLoyaltyTiers::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateLoyaltyTier::class)
        ->assertSuccessful();
});

test('can create a loyalty tier', function (): void {
    Livewire::test(CreateLoyaltyTier::class)
        ->fillForm([
            'tier' => 'gold',
            'points_threshold' => 500,
            'discount_bonus_pct' => '10.00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(LoyaltyTier::query()->where('tier', 'gold')->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $tier = LoyaltyTier::factory()->create(['tier' => 'standard']);

    Livewire::test(EditLoyaltyTier::class, ['record' => $tier->id])
        ->assertSuccessful();
});

test('can edit a loyalty tier', function (): void {
    $tier = LoyaltyTier::factory()->create(['tier' => 'silver']);

    Livewire::test(EditLoyaltyTier::class, ['record' => $tier->id])
        ->fillForm(['points_threshold' => 250])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tier->fresh()->points_threshold)->toBe(250);
});
