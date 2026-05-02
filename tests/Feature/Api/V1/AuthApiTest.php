<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('register creates a patient and returns a token', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test Patient',
        'email'                 => 'patient@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'gdpr_consent'          => true,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect(User::where('email', 'patient@example.com')->first()->hasRole('patient'))->toBeTrue();
});

test('login returns a token for a patient', function (): void {
    $user = User::factory()->create(['password' => 'Password123!']);
    $user->assignRole('patient');

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user']);
});

test('non-patient login is rejected with 403', function (): void {
    $admin = User::factory()->create(['password' => 'Password123!']);
    $admin->assignRole('admin');

    $this->postJson('/api/v1/auth/login', [
        'email'    => $admin->email,
        'password' => 'Password123!',
    ])->assertForbidden();
});

test('logout revokes the token', function (): void {
    $user = User::factory()->create();
    $user->assignRole('patient');
    $token = $user->createToken('booking-api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('logout does not crash when authenticated via session guard (TransientToken)', function (): void {
    $user = User::factory()->create();
    $user->assignRole('patient');

    $this->actingAs($user)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();
});
