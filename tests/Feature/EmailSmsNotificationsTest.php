<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentNoShowNotification;
use App\Notifications\AppointmentRequestedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

// ── Controller dispatch ──────────────────────────────────────────────────────

it('dispatches AppointmentBookedNotification after store()', function (): void {
    $patient = User::factory()->create(['phone_verified_at' => now()]);
    $doctor = Doctor::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);
    $service = Service::factory()->create();

    $this->actingAs($patient, 'sanctum')
        ->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'slot_id' => $slot->id,
        ])
        ->assertCreated();

    Notification::assertSentTo($patient, AppointmentBookedNotification::class);
});

it('dispatches AppointmentRequestedNotification after requestStore()', function (): void {
    $patient = User::factory()->create(['phone_verified_at' => now()]);
    $service = Service::factory()->create();

    $this->actingAs($patient, 'sanctum')
        ->postJson('/api/v1/appointments/request', [
            'service_id' => $service->id,
            'preferred_date' => now()->addDays(7)->format('Y-m-d'),
        ])
        ->assertCreated();

    Notification::assertSentTo($patient, AppointmentRequestedNotification::class);
});

// ── Observer status transitions ──────────────────────────────────────────────

it('dispatches AppointmentConfirmedNotification when status changes to Confirmed', function (): void {
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Pending,
    ]);

    $appointment->update(['status' => AppointmentStatus::Confirmed]);

    Notification::assertSentTo($patient, AppointmentConfirmedNotification::class);
});

it('dispatches AppointmentCompletedNotification when status changes to Completed', function (): void {
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    Notification::assertSentTo($patient, AppointmentCompletedNotification::class);
});

it('dispatches AppointmentCancelledNotification when status changes to Cancelled', function (): void {
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Pending,
    ]);

    $appointment->update(['status' => AppointmentStatus::Cancelled]);

    Notification::assertSentTo($patient, AppointmentCancelledNotification::class);
});

it('dispatches AppointmentNoShowNotification when status changes to NoShow', function (): void {
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $appointment->update(['status' => AppointmentStatus::NoShow]);

    Notification::assertSentTo($patient, AppointmentNoShowNotification::class);
});

it('does not dispatch any notification when only notes change', function (): void {
    $patient = User::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'status' => AppointmentStatus::Confirmed,
    ]);
    Notification::fake(); // reset after creation dispatches

    $appointment->update(['notes' => 'Some updated notes']);

    Notification::assertNothingSent();
});

// ── via() channel routing ────────────────────────────────────────────────────

it('via() returns mail channel for notification_channel=email', function (): void {
    $notifiable = User::factory()->make(['notification_channel' => 'email']);
    $slot = ScheduleSlot::factory()->make(['date' => now()->addDays(3), 'start_time' => '09:00']);
    $doctor = Doctor::factory()->make();
    $doctor->setRelation('user', User::factory()->make());
    $appointment = Appointment::factory()->make();
    $appointment->setRelation('slot', $slot);
    $appointment->setRelation('doctor', $doctor);
    $appointment->setRelation('service', Service::factory()->make());
    $appointment->setRelation('patient', $notifiable);

    $notification = new AppointmentBookedNotification($appointment);

    expect($notification->via($notifiable))->toBe(['mail']);
});

it('via() returns vonage channel for notification_channel=sms', function (): void {
    $notifiable = User::factory()->make(['notification_channel' => 'sms']);
    $slot = ScheduleSlot::factory()->make(['date' => now()->addDays(3), 'start_time' => '09:00']);
    $doctor = Doctor::factory()->make();
    $doctor->setRelation('user', User::factory()->make());
    $appointment = Appointment::factory()->make();
    $appointment->setRelation('slot', $slot);
    $appointment->setRelation('doctor', $doctor);
    $appointment->setRelation('service', Service::factory()->make());
    $appointment->setRelation('patient', $notifiable);

    $notification = new AppointmentBookedNotification($appointment);

    expect($notification->via($notifiable))->toBe(['vonage']);
});

it('via() returns both channels for notification_channel=both', function (): void {
    $notifiable = User::factory()->make(['notification_channel' => 'both']);
    $slot = ScheduleSlot::factory()->make(['date' => now()->addDays(3), 'start_time' => '09:00']);
    $doctor = Doctor::factory()->make();
    $doctor->setRelation('user', User::factory()->make());
    $appointment = Appointment::factory()->make();
    $appointment->setRelation('slot', $slot);
    $appointment->setRelation('doctor', $doctor);
    $appointment->setRelation('service', Service::factory()->make());
    $appointment->setRelation('patient', $notifiable);

    $notification = new AppointmentBookedNotification($appointment);

    expect($notification->via($notifiable))->toBe(['mail', 'vonage']);
});
