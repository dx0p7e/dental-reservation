<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('appointments:send-reminders')]
#[Description('Send reminder emails for appointments starting in ~24 hours')]
class SendAppointmentReminders extends Command
{
    public function handle(): void
    {
        $appointments = Appointment::query()
            ->join('schedule_slots', 'schedule_slots.id', '=', 'appointments.slot_id')
            ->where('appointments.status', AppointmentStatus::Confirmed)
            ->whereNull('appointments.reminder_sent_at')
            ->whereRaw(
                "datetime(schedule_slots.date || ' ' || schedule_slots.start_time) BETWEEN datetime(?) AND datetime(?)",
                [now()->addHours(23)->format('Y-m-d H:i:s'), now()->addHours(25)->format('Y-m-d H:i:s')]
            )
            ->select('appointments.*')
            ->with('patient', 'doctor.user', 'service', 'slot')
            ->get();

        foreach ($appointments as $appointment) {
            Mail::to($appointment->patient->email)->send(new AppointmentReminder($appointment));

            $appointment->update(['reminder_sent_at' => now()]);
        }
    }
}
