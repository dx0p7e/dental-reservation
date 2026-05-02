<?php

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
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
    Livewire::test(ListServices::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateService::class)
        ->assertSuccessful();
});

test('can create a service', function (): void {
    Livewire::test(CreateService::class)
        ->fillForm([
            'name' => 'Teeth Cleaning',
            'description' => null,
            'duration_minutes' => 30,
            'price' => '50.00',
            'complexity' => 'simple',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Service::query()->where('name', 'Teeth Cleaning')->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $service = Service::factory()->create();

    Livewire::test(EditService::class, ['record' => $service->id])
        ->assertSuccessful();
});

test('can edit a service', function (): void {
    $service = Service::factory()->create();

    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm(['name' => 'Updated Service'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($service->fresh()->name)->toBe('Updated Service');
});
