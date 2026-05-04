<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Mail::fake();
});

test('patient can create a booking', function (): void {
    $patient = User::factory()->create(['phone_verified_at' => now()]);
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertCreated();

    expect($slot->fresh()->is_booked)->toBeTrue();
});

test('double-booking returns 404', function (): void {
    $patient = User::factory()->create(['phone_verified_at' => now()]);
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => true]);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertNotFound();
});

test('patient cannot see another patient appointments', function (): void {
    $patientA = User::factory()->create();
    $patientA->assignRole('patient');
    $patientB = User::factory()->create();
    $patientB->assignRole('patient');
    Sanctum::actingAs($patientA);

    Appointment::factory()->create(['patient_id' => $patientB->id]);

    $response = $this->getJson('/api/v1/appointments');
    $response->assertOk()->assertJsonCount(0, 'data');
});

test('patient can cancel a pending appointment', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Pending,
    ]);

    $this->deleteJson("/api/v1/appointments/{$appointment->id}")
        ->assertNoContent();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('patient cannot cancel a completed appointment', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Completed,
    ]);

    $this->deleteJson("/api/v1/appointments/{$appointment->id}")
        ->assertUnprocessable();
});

test('non-owner cancel returns 403', function (): void {
    $patientA = User::factory()->create();
    $patientA->assignRole('patient');
    $patientB = User::factory()->create();
    $patientB->assignRole('patient');
    Sanctum::actingAs($patientA);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patientB->id,
        'status' => AppointmentStatus::Pending,
    ]);

    $this->deleteJson("/api/v1/appointments/{$appointment->id}")
        ->assertForbidden();
});

test('patient can submit a booking request', function (): void {
    $patient = User::factory()->create(['phone_verified_at' => now()]);
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $service = Service::factory()->create();

    $response = $this->postJson('/api/v1/appointments/request', [
        'service_id' => $service->id,
        'preferred_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'slot_id' => null,
        'doctor_id' => null,
        'status' => AppointmentStatus::Pending->value,
    ]);
});

test('booking request with past preferred_date is rejected', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $service = Service::factory()->create();

    $this->postJson('/api/v1/appointments/request', [
        'service_id' => $service->id,
        'preferred_date' => now()->subDay()->toDateString(),
    ])->assertUnprocessable();
});

test('booking request without authentication is rejected', function (): void {
    $service = Service::factory()->create();

    $this->postJson('/api/v1/appointments/request', [
        'service_id' => $service->id,
        'preferred_date' => now()->addDays(3)->toDateString(),
    ])->assertUnauthorized();
});

test('booking request without service_id is rejected', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments/request', [
        'preferred_date' => now()->addDays(3)->toDateString(),
    ])->assertUnprocessable();
});

test('appointment resource returns null slot and doctor for request-based appointments', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $service = Service::factory()->create();

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'slot_id' => null,
        'doctor_id' => null,
        'status' => AppointmentStatus::Pending,
    ]);

    $response = $this->getJson('/api/v1/appointments')->assertOk();

    expect($response->json('data.0.slot'))->toBeNull();
    expect($response->json('data.0.doctor'))->toBeNull();
});
