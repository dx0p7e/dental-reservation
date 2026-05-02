<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

test('registration requires gdpr consent', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['gdpr_consent']);
});

test('registration with gdpr consent records timestamp', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'gdpr_consent'          => true,
    ]);

    $response->assertCreated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->gdpr_consent_at)->not->toBeNull();
});

test('registration with gdpr consent false is rejected', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'gdpr_consent'          => false,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['gdpr_consent']);
});
