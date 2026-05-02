<?php

use App\Models\Doctor;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

test('doctor canAccessPanel returns true for the doctor panel', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');

    $panel = Filament::getPanel('doctor');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('patient canAccessPanel returns false for the doctor panel', function (): void {
    $user = User::factory()->create(['role' => 'patient']);
    $user->assignRole('patient');

    $panel = Filament::getPanel('doctor');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('admin canAccessPanel returns false for the doctor panel', function (): void {
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $panel = Filament::getPanel('doctor');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('doctor canAccessPanel returns false for the admin panel', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('admin canAccessPanel returns true for the admin panel', function (): void {
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('authenticated doctor is redirected into the doctor panel', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');
    Doctor::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/doctor');

    $response->assertRedirectContains('/doctor/');
});

test('unauthenticated user is redirected to doctor login', function (): void {
    $response = $this->get('/doctor/doctor-schedules');

    $response->assertRedirect('/doctor/login');
});
