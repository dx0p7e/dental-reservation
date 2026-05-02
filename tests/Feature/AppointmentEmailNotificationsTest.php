<?php

use App\Console\Commands\SendAppointmentReminders;
use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Models\ScheduleSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('does not send any mail when only notes change', function (): void {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);
    Mail::fake(); // reset after creation

    $appointment->update(['notes' => 'Updated notes']);

    Mail::assertNothingSent();
});

it('sends a reminder email to appointments starting in the 23-25h window', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $slot = ScheduleSlot::factory()->create([
        'date' => '2025-01-02',
        'start_time' => '10:30:00',
    ]);

    $appointment = Appointment::factory()->create([
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Confirmed,
        'reminder_sent_at' => null,
    ]);

    $this->artisan(SendAppointmentReminders::class);

    Mail::assertSent(AppointmentReminder::class, function (AppointmentReminder $mail) use ($appointment): bool {
        return $mail->appointment->id === $appointment->id;
    });

    Carbon::setTestNow();
});

it('stamps reminder_sent_at after sending a reminder', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $slot = ScheduleSlot::factory()->create([
        'date' => '2025-01-02',
        'start_time' => '10:30:00',
    ]);

    $appointment = Appointment::factory()->create([
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Confirmed,
        'reminder_sent_at' => null,
    ]);

    $this->artisan(SendAppointmentReminders::class);

    expect($appointment->fresh()->reminder_sent_at)->not->toBeNull();

    Carbon::setTestNow();
});

it('skips appointments that already have reminder_sent_at set', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $slot = ScheduleSlot::factory()->create([
        'date' => '2025-01-02',
        'start_time' => '10:30:00',
    ]);

    Appointment::factory()->create([
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Confirmed,
        'reminder_sent_at' => now()->subHour(),
    ]);

    $this->artisan(SendAppointmentReminders::class);

    Mail::assertNotSent(AppointmentReminder::class);

    Carbon::setTestNow();
});

it('skips Pending and Cancelled appointments in the reminder window', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $slot = ScheduleSlot::factory()->create([
        'date' => '2025-01-02',
        'start_time' => '10:30:00',
    ]);

    Appointment::factory()->create([
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Pending,
        'reminder_sent_at' => null,
    ]);

    Appointment::factory()->create([
        'slot_id' => $slot->id,
        'status' => AppointmentStatus::Cancelled,
        'reminder_sent_at' => null,
    ]);

    $this->artisan(SendAppointmentReminders::class);

    Mail::assertNotSent(AppointmentReminder::class);

    Carbon::setTestNow();
});
