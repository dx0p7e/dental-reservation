<?php

use App\Filament\Resources\Doctors\Pages\CreateDoctor;
use App\Filament\Resources\Doctors\Pages\EditDoctor;
use App\Filament\Resources\Doctors\Pages\ListDoctors;
use App\Models\Doctor;
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
    Livewire::test(ListDoctors::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateDoctor::class)
        ->assertSuccessful();
});

test('can create a doctor', function (): void {
    $user = User::factory()->create();

    Livewire::test(CreateDoctor::class)
        ->fillForm([
            'user_id' => $user->id,
            'specialization' => 'Orthodontics',
            'bio' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Doctor::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $doctor = Doctor::factory()->create();

    Livewire::test(EditDoctor::class, ['record' => $doctor->id])
        ->assertSuccessful();
});

test('can edit a doctor', function (): void {
    $doctor = Doctor::factory()->create();

    Livewire::test(EditDoctor::class, ['record' => $doctor->id])
        ->fillForm(['specialization' => 'Periodontics'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($doctor->fresh()->specialization)->toBe('Periodontics');
});
