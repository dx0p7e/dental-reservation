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

test('debug access to /doctor root', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');
    Doctor::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/doctor');
    $response->assertRedirectContains('/doctor/');
});

test('debug access to /doctor/doctor-appointments', function (): void {
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');
    Doctor::factory()->create(['user_id' => $user->id]);

    $panel = Filament::getPanel('doctor');
    expect($user->canAccessPanel($panel))->toBeTrue();

    $response = $this->actingAs($user)->get('/doctor/doctor-appointments');
    $response->assertOk();
});
