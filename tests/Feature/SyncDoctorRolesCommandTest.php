<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

test('command ensures all doctor-enum users have the spatie doctor role', function (): void {
    $doctor = User::factory()->create(['role' => 'doctor']);

    // Observer now auto-assigns on creation; command must still succeed (idempotent)
    $this->artisan('doctor:sync-roles')->assertSuccessful();

    expect($doctor->fresh()->hasRole('doctor'))->toBeTrue();
});

test('command skips users who already have the spatie doctor role', function (): void {
    $doctor = User::factory()->create(['role' => 'doctor']);
    $doctor->assignRole('doctor');

    $this->artisan('doctor:sync-roles')->assertSuccessful();

    // Should still have exactly one role entry (no duplicate assignment)
    expect($doctor->fresh()->roles->count())->toBe(1);
});

test('command does not assign doctor role to patients', function (): void {
    $patient = User::factory()->create(['role' => 'patient']);

    $this->artisan('doctor:sync-roles')->assertSuccessful();

    expect($patient->fresh()->hasRole('doctor'))->toBeFalse();
});
