<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');
    $this->actingAs($admin);
    $this->admin = $admin;
});

test('list page renders', function (): void {
    Livewire::test(ListUsers::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateUser::class)
        ->assertSuccessful();
});

test('can create a user and assigns spatie role', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => 'patient',
            'notification_channel' => 'email',
            'email_verified_at' => false,
            'phone_verified_at' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('patient'))->toBeTrue();
});

test('creating a doctor user auto-creates a doctor profile', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Dr. Smith',
            'email' => 'drsmith@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => 'doctor',
            'notification_channel' => 'email',
            'email_verified_at' => false,
            'phone_verified_at' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'drsmith@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(Doctor::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('edit form role change syncs spatie role and updates role column', function (): void {
    $user = User::factory()->create(['role' => 'patient']);
    $user->assignRole('patient');

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['role' => 'admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->role)->toBe('admin')
        ->and($user->fresh()->hasRole('admin'))->toBeTrue()
        ->and($user->fresh()->hasRole('patient'))->toBeFalse();
});

test('editing role to doctor creates doctor profile if missing', function (): void {
    $user = User::factory()->create(['role' => 'patient']);
    $user->assignRole('patient');

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['role' => 'doctor'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Doctor::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('reset password action updates the password hash', function (): void {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->callAction('resetPassword', [
            'password' => 'newpassword99',
            'password_confirmation' => 'newpassword99',
        ])
        ->assertSuccessful();

    expect(Hash::check('newpassword99', $user->fresh()->password))->toBeTrue();
});

test('verify email action sets email_verified_at', function (): void {
    $user = User::factory()->unverified()->create();

    Livewire::test(ListUsers::class)
        ->callTableAction('verifyEmail', $user)
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('revoke verifications action clears both verified-at columns', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);

    Livewire::test(ListUsers::class)
        ->callTableAction('revokeVerifications', $user)
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at)->toBeNull()
        ->and($user->fresh()->phone_verified_at)->toBeNull();
});

test('cannot delete own user record', function (): void {
    expect(UserResource::canDelete($this->admin))->toBeFalse();

    $otherUser = User::factory()->create();
    expect(UserResource::canDelete($otherUser))->toBeTrue();
});
