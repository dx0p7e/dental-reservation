## Why

The appointment lifecycle is complete end-to-end — patients can book, doctors manage slots, loyalty points are awarded — but nothing communicates back to the patient outside the admin panel. A confirmation email at booking, a reminder 24 hours ahead, and a cancellation notice are the minimum table stakes for any healthcare scheduling product.

## What Changes

- **`AppointmentConfirmed` Mailable + Blade template** — dispatched in `AppointmentObserver@created`. Contains: service name, doctor full name, appointment date and start time, clinic address.
- **`AppointmentCancelled` Mailable + Blade template** — dispatched in `AppointmentObserver@updated` when status transitions to `Cancelled`. Same data shape plus a "contact us to rebook" prompt.
- **`AppointmentReminder` Mailable + Blade template** — dispatched by a scheduled Artisan command, not the observer.
- **`SendAppointmentReminders` Artisan command** — queries `Confirmed` appointments whose slot falls in the 23–25-hour window from now, filters to those without `reminder_sent_at`, sends `AppointmentReminder`, stamps `reminder_sent_at`.
- **`reminder_sent_at` migration** — adds nullable timestamp column to `appointments` table as a duplicate-send guard.
- **Scheduler registration** — `Schedule::command('appointments:send-reminders')->hourly()` in `routes/console.php`.
- **`AppointmentObserver`** — add `created()` hook for confirmation mail; extend `updated()` to branch on `Cancelled` status alongside the existing `Completed` branch.

> **Codebase notes:** `AppointmentObserver@created` does not currently exist — only `updated()`. The `reminder_sent_at` column does not exist on `appointments`. Scheduling uses `routes/console.php` (not `app/Console/Kernel.php`) via `Schedule::command()`. The patient relationship on `Appointment` is `->patient()` returning `User`; email is `$appointment->patient->email`. Slot date/time is on `ScheduleSlot` as separate `date` and `start_time` columns (no `starts_at` field), accessed via `$appointment->slot`.

## Capabilities

### New Capabilities

- `appointment-confirmation-email`: Patient receives a booking confirmation email immediately when an appointment is created.
- `appointment-cancellation-email`: Patient receives a cancellation notice email when their appointment status transitions to Cancelled.
- `appointment-reminder-email`: Patient receives a reminder email approximately 24 hours before a Confirmed appointment.

### Modified Capabilities

*(none — no existing spec requirements change)*

## Impact

- `app/Mail/AppointmentConfirmed.php` — new Mailable
- `app/Mail/AppointmentCancelled.php` — new Mailable
- `app/Mail/AppointmentReminder.php` — new Mailable
- `resources/views/mail/appointment/confirmed.blade.php` — new template
- `resources/views/mail/appointment/cancelled.blade.php` — new template
- `resources/views/mail/appointment/reminder.blade.php` — new template
- `app/Console/Commands/SendAppointmentReminders.php` — new Artisan command
- `app/Observers/AppointmentObserver.php` — add `created()`, extend `updated()`
- `database/migrations/xxxx_add_reminder_sent_at_to_appointments_table.php` — new migration
- `routes/console.php` — scheduler registration
- No new routes, no new models, no dependency changes
