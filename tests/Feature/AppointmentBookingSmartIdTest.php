<?php

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Notification::fake();
});

function makeVerifiedPatient(bool $smartIdVerified = false): array
{
    $patient = User::factory()->create([
        'role' => 'patient',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'smart_id_verified_at' => $smartIdVerified ? now() : null,
    ]);
    $patient->assignRole('patient');

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    return compact('patient', 'doctor', 'service', 'slot');
}

test('booking creates Confirmed appointment when user has Smart-ID verified', function (): void {
    ['patient' => $patient, 'doctor' => $doctor, 'service' => $service, 'slot' => $slot] = makeVerifiedPatient(true);

    Sanctum::actingAs($patient);

    $response = $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Confirmed->value,
    ]);
});

test('booking creates Pending appointment when user does not have Smart-ID verified', function (): void {
    ['patient' => $patient, 'doctor' => $doctor, 'service' => $service, 'slot' => $slot] = makeVerifiedPatient(false);

    Sanctum::actingAs($patient);

    $response = $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Pending->value,
    ]);
});

test('Smart-ID verified booking sends AppointmentConfirmedNotification', function (): void {
    ['patient' => $patient, 'doctor' => $doctor, 'service' => $service, 'slot' => $slot] = makeVerifiedPatient(true);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertCreated();

    Notification::assertSentTo($patient, AppointmentConfirmedNotification::class);
    Notification::assertNotSentTo($patient, AppointmentBookedNotification::class);
});

test('non-Smart-ID booking sends AppointmentBookedNotification', function (): void {
    ['patient' => $patient, 'doctor' => $doctor, 'service' => $service, 'slot' => $slot] = makeVerifiedPatient(false);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertCreated();

    Notification::assertSentTo($patient, AppointmentBookedNotification::class);
    Notification::assertNotSentTo($patient, AppointmentConfirmedNotification::class);
});
