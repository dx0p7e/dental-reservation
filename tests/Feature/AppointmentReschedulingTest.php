<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentRescheduledNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Notification::fake();
});

// Helpers

function makePatientWithAppointment(AppointmentStatus $status = AppointmentStatus::Pending): array
{
    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $patient->assignRole('patient');

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $oldSlot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => true]);
    $newSlot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $oldSlot->id,
        'status' => $status,
    ]);

    return compact('patient', 'doctor', 'oldSlot', 'newSlot', 'appointment');
}

// --- T9.2: successful reschedule for pending appointment ---

it('reschedules a pending appointment and returns 200', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk()
        ->assertJsonPath('data.id', $appointment->id);

    expect($appointment->fresh()->slot_id)->toBe($newSlot->id);
});

// --- T9.3: successful reschedule for confirmed appointment ---

it('reschedules a confirmed appointment and returns 200', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment(AppointmentStatus::Confirmed);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk();

    expect($appointment->fresh()->slot_id)->toBe($newSlot->id);
});

// --- T9.4: 403 for a different patient ---

it('returns 403 when a different patient tries to reschedule', function (): void {
    ['appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();

    $other = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $other->assignRole('patient');

    Sanctum::actingAs($other);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertForbidden();
});

// --- T9.5: 422 when appointment has no slot ---

it('returns 422 when appointment has no slot', function (): void {
    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $patient->assignRole('patient');

    $doctor = Doctor::factory()->create();
    $newSlot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'slot_id' => null,
        'status' => AppointmentStatus::Pending,
    ]);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertUnprocessable();
});

// --- T9.6: 422 for cancelled appointment ---

it('returns 422 when appointment is cancelled', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment(AppointmentStatus::Cancelled);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertUnprocessable();
});

// --- T9.7: 422 for completed appointment ---

it('returns 422 when appointment is completed', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment(AppointmentStatus::Completed);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertUnprocessable();
});

// --- T9.8: 404 when slot belongs to a different doctor ---

it('returns 404 when new slot belongs to a different doctor', function (): void {
    ['patient' => $patient, 'appointment' => $appointment] = makePatientWithAppointment();

    $otherDoctor = Doctor::factory()->create();
    $otherSlot = ScheduleSlot::factory()->create(['doctor_id' => $otherDoctor->id, 'is_booked' => false]);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $otherSlot->id])
        ->assertNotFound();
});

// --- T9.9: 422 when new slot is already booked ---

it('returns 422 when new slot is already booked', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'doctor' => $doctor] = makePatientWithAppointment();

    $takenSlot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => true]);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $takenSlot->id])
        ->assertUnprocessable();
});

// --- T9.10: old slot is freed ---

it('sets old slot is_booked to false after reschedule', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'oldSlot' => $oldSlot, 'newSlot' => $newSlot] = makePatientWithAppointment();

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk();

    expect($oldSlot->fresh()->is_booked)->toBeFalse();
});

// --- T9.11: new slot is booked ---

it('sets new slot is_booked to true after reschedule', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk();

    expect($newSlot->fresh()->is_booked)->toBeTrue();
});

// --- T9.12: notification dispatched ---

it('sends AppointmentRescheduledNotification to patient on success', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk();

    Notification::assertSentTo($patient, AppointmentRescheduledNotification::class);
});

// --- T9.13: show() returns 200 for owning patient ---

it('show returns 200 with appointment resource for the owning patient', function (): void {
    ['patient' => $patient, 'appointment' => $appointment] = makePatientWithAppointment();

    Sanctum::actingAs($patient);

    $this->getJson("/api/v1/appointments/{$appointment->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $appointment->id);
});

// --- T9.14: show() returns 403 for a different patient ---

it('show returns 403 for a different patient', function (): void {
    ['appointment' => $appointment] = makePatientWithAppointment();

    $other = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $other->assignRole('patient');

    Sanctum::actingAs($other);

    $this->getJson("/api/v1/appointments/{$appointment->id}")
        ->assertForbidden();
});

// --- T9.15: returns 422 when appointment has already been rescheduled ---

it('returns 422 when appointment has already been rescheduled once', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();
    $appointment->update(['rescheduled_at' => now()]);

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'This appointment has already been rescheduled once and cannot be rescheduled again.']);
});

// --- T9.16: stamps rescheduled_at on first successful reschedule ---

it('stamps rescheduled_at on successful reschedule', function (): void {
    ['patient' => $patient, 'appointment' => $appointment, 'newSlot' => $newSlot] = makePatientWithAppointment();

    expect($appointment->rescheduled_at)->toBeNull();

    Sanctum::actingAs($patient);

    $this->patchJson("/api/v1/appointments/{$appointment->id}/reschedule", ['slot_id' => $newSlot->id])
        ->assertOk();

    expect($appointment->fresh()->rescheduled_at)->not->toBeNull();
});
