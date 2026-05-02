## Context

The appointment model (`Appointment`) is connected to `User` (via `patient_id`/`patient()`), `Doctor` (via `doctor_id`/`doctor()`), `Service` (via `service_id`/`service()`), and `ScheduleSlot` (via `slot_id`/`slot()`). The slot holds `date` (cast to `date:Y-m-d`) and `start_time` (string, e.g., `"09:00:00"`) — there is no `starts_at` timestamp column. Doctor name is `$appointment->doctor->user->name`. The app timezone is `UTC`.

`AppointmentObserver` already exists with a single `updated()` method that handles loyalty points on `Completed` status. `routes/console.php` is the scheduling entry point (already registers `slots:generate --daily()`). No `app/Console/Kernel.php` is in use.

## Goals / Non-Goals

**Goals:**
- Send confirmation mail synchronously on appointment creation
- Send cancellation mail synchronously on status → Cancelled
- Send reminder mail ~24 hours before Confirmed appointments via an hourly cron
- Guard against duplicate reminders via `reminder_sent_at` timestamp
- Keep all mail synchronous (no queued jobs) — `MAIL_MAILER=log` in dev

**Non-Goals:**
- Queue-based async dispatch
- SMS or push notifications
- Patient-initiated cancellation UI
- Reminders for Pending appointments
- Retroactive reminders for existing appointments
- Clinic timezone conversion (UTC throughout)

## Decisions

### 1 — Mail dispatched synchronously from the Observer (not queued)

**Decision:** Call `Mail::to(...)->send(...)` directly inside `AppointmentObserver@created` and `@updated`. No jobs, no `queue` driver.

**Rationale:** Thesis scope. `MAIL_MAILER=log` in development means no real email is sent and no network latency. Adding a queue introduces a worker process and `.env` complexity not warranted here.

**Alternative:** Dispatch a queued `SendAppointmentConfirmationEmail` job. Deferred for production hardening.

### 2 — Reminder window: slot date+time reconstructed into a Carbon datetime for the query

**Decision:** The reminder query cannot use `starts_at` (it doesn't exist). Instead, join `schedule_slots` and filter on `CONCAT(date, ' ', start_time)` cast as datetime, or load candidates by slot date and filter in PHP.

**Preferred approach:** Use a database-level filter:
```php
Appointment::query()
    ->join('schedule_slots', 'schedule_slots.id', '=', 'appointments.slot_id')
    ->where('appointments.status', AppointmentStatus::Confirmed)
    ->whereNull('appointments.reminder_sent_at')
    ->whereRaw("CONCAT(schedule_slots.date, ' ', schedule_slots.start_time) BETWEEN ? AND ?", [
        now()->addHours(23)->format('Y-m-d H:i:s'),
        now()->addHours(25)->format('Y-m-d H:i:s'),
    ])
    ->select('appointments.*')
    ->with('patient', 'doctor.user', 'service', 'slot')
    ->get()
```

**Rationale:** Keeps the PHP code simple; a single query with no in-memory filtering.

**Alternative considered:** `->whereBetween('starts_at', ...)` — field does not exist.

### 3 — `reminder_sent_at` added as a nullable timestamp via migration (not a boolean flag)

**Decision:** Use a nullable `timestamp` rather than a boolean `reminder_sent`.

**Rationale:** A timestamp is strictly more informative — it records *when* the reminder was sent, which is useful for debugging. Cost is identical.

### 4 — Blade templates under `resources/views/mail/appointment/`

**Decision:** Three Blade files: `confirmed.blade.php`, `cancelled.blade.php`, `reminder.blade.php`. Plain-text style (no Mailable `->markdown()` component). Use `@component('mail::message')` from Laravel's built-in mail component if available, otherwise plain HTML with inline styles.

**Rationale:** Consistency with any existing mail views in the project. No external template dependency.

### 5 — Doctor full name via `$appointment->doctor->user->name`

**Decision:** The `Doctor` model belongs to `User` via `user_id`. Doctor display name is `$appointment->doctor->user->name`. Mailables must eager-load `doctor.user` to avoid N+1 on the Mailable constructor.

**Rationale:** Reflects the actual model structure. The `Doctor` model has no standalone `name` field.

## Risks / Trade-offs

- **Synchronous mail on create blocks the HTTP response** → Acceptable for thesis; mitigated by `MAIL_MAILER=log` in dev (no network call).
- **CONCAT date+time query is MySQL-specific** → The project uses MySQL (confirmed by docker config); SQLite test DB may need a workaround in tests (use Carbon date math in a `whereDate` + `whereTime` pair or seed slots that fall in the window naturally).
- **Observer `created()` fires even for factory-created appointments in tests** → Tests for other features that use `Appointment::factory()->create()` will now trigger mail dispatch. `Mail::fake()` must be called in those tests or the global `TestCase` to avoid side effects.
- **`reminder_sent_at` not in `$fillable`** → Use `$appointment->update(['reminder_sent_at' => now()])` which bypasses fillable, or add to fillable. Adding to fillable is cleaner.

## Open Questions

*(none)*
