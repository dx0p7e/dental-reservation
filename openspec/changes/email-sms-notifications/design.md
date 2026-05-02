## Context

**Existing app state relevant to this change:**

- `AppointmentObserver` already exists with `created()` (sends `AppointmentConfirmed` Mailable — incorrect: fires with status `Pending`) and `updated()` (sends `AppointmentCancelled` Mailable + loyalty-points logic). These must be reworked.
- Three Mailables exist: `app/Mail/AppointmentConfirmed.php`, `AppointmentCancelled.php`, `AppointmentReminder.php`. The first two are superseded and will be deleted; `AppointmentReminder` and `SendAppointmentReminders` command are out of scope and left as-is.
- `AppointmentStatus` enum: `Pending`, `Confirmed`, `Cancelled`, `Completed`, `NoShow`.
- `Appointment` → `patient()` returns `User`; patient email is `$appointment->patient->email`.
- Slot date/time: `$appointment->slot->date` (cast to `date:Y-m-d`) and `$appointment->slot->start_time` (string `"HH:MM:SS"`). No combined `starts_at`.
- Doctor name: `$appointment->doctor->user->name`.
- `User` already uses the `Notifiable` trait — `$user->notify()` works out of the box.
- `jobs` + `failed_jobs` tables already exist via `0001_01_01_000002_create_jobs_table.php`.
- `AppointmentController` has two creation methods: `store()` (slot-assigned booking) and `requestStore()` (date-request, no slot).
- `reminder_sent_at` column already exists on `appointments` from a prior migration.
- The `appointment-email-notifications` change is partially implemented and is superseded by this change.

## Goals / Non-Goals

**Goals:**
- Notify patients for all six appointment events (booked, requested, confirmed, completed, cancelled, no-show)
- Support email, SMS (Vonage), or both based on user preference
- Queue all notifications via the database driver
- Differentiate "booked" (slot assigned) from "requested" (no slot yet) at creation time
- Add `notification_channel` enum column to `users`

**Non-Goals:**
- Profile UI for editing `notification_channel` (next change)
- Appointment reminder (day-before) notifications — `AppointmentReminder` Mailable and its command are left as-is
- In-app notification bell
- Patient opt-out / unsubscribe flow
- Admin or doctor notifications
- Notification log / history table

## Decisions

### 1 — Laravel Notifications over raw Mailables

**Decision:** Use `Illuminate\Notifications\Notification` with `ShouldQueue`, not `Mail::to()->send()` with Mailable classes.

**Rationale:** Notifications natively multiplex channels per class via `via()`. The single `via()` method reading `$notifiable->notification_channel` is the cleanest expression of the user-preference requirement. Raw Mailables would require manual channel branching at every dispatch site.

### 2 — Notifications queued via database driver

**Decision:** All six Notification classes implement `ShouldQueue`. `QUEUE_CONNECTION=database`. The `jobs` table already exists — no migration needed.

**Rationale:** Queueing isolates mail/SMS latency from the HTTP response. The database driver requires no external infrastructure beyond what's already present.

### 3 — Creation events dispatched from controller, not observer

**Decision:** `AppointmentController::store()` dispatches `AppointmentBookedNotification`; `requestStore()` dispatches `AppointmentRequestedNotification`. The observer has no `created()` hook.

**Rationale:** The observer cannot distinguish between the two creation paths (both call `Appointment::create()`). Dispatching from the controller is the only clean way to send semantically distinct notifications for slot-booking vs date-request.

### 4 — Status-transition events dispatched from observer

**Decision:** `AppointmentObserver::updated()` handles `Confirmed`, `Completed`, `Cancelled`, `NoShow` transitions. The loyalty-points logic for `Completed` is retained in the same branch (no change to that logic).

**Rationale:** The observer already owns status-change side effects. Centralising transition notifications there avoids scattering `->notify()` calls across every controller and Filament action that can change status.

### 5 — Vonage for SMS

**Decision:** Use `laravel-notification-channels/vonage` package. SMS copy in Lithuanian, ≤ 160 characters per message.

**Rationale:** Vonage has free trial credits suitable for demo. The package integrates via the standard notification channel contract (`toVonage()`). Alternative providers (Twilio, AWS SNS) are heavier dependencies for the same outcome.

### 6 — Blade markdown email templates under `resources/views/notifications/appointments/`

**Decision:** Use `@component('mail::message')` Blade markdown layout. Templates live at `resources/views/notifications/appointments/{event}.blade.php`.

**Rationale:** Consistent with Laravel's Notification mail convention. Markdown layout is rendered to HTML automatically. Clean path separate from the legacy `resources/views/mail/appointment/` path used by the old Mailables.

### 7 — Delete superseded Mailables (`AppointmentConfirmed`, `AppointmentCancelled`)

**Decision:** Delete `app/Mail/AppointmentConfirmed.php` and `app/Mail/AppointmentCancelled.php`. Remove their imports and usage from `AppointmentObserver`.

**Rationale:** Leaving dead Mailables alongside Notifications creates ambiguity. The observer will no longer use them.

## Architecture

```
HTTP Request
  └── AppointmentController::store()
        └── Appointment::create() [Pending, slot assigned]
              └── dispatch AppointmentBookedNotification → patient

  └── AppointmentController::requestStore()
        └── Appointment::create() [Pending, no slot]
              └── dispatch AppointmentRequestedNotification → patient

AppointmentObserver::updated() [status changed]
  ├── Pending → Confirmed    → AppointmentConfirmedNotification → patient
  ├── *       → Completed   → AppointmentCompletedNotification → patient + loyalty logic
  ├── *       → Cancelled   → AppointmentCancelledNotification → patient
  └── *       → NoShow      → AppointmentNoShowNotification   → patient

Each Notification::via() reads $notifiable->notification_channel:
  'email' (default) → ['mail']
  'sms'             → ['vonage']
  'both'            → ['mail', 'vonage']
```

## Notification Class Structure (repeated pattern)

```php
class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return match($notifiable->notification_channel) {
            'sms'  => ['vonage'],
            'both' => ['mail', 'vonage'],
            default => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->markdown('notifications.appointments.booked', [
                'appointment' => $this->appointment,
            ]);
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $slot  = $this->appointment->slot;
        $date  = $slot->date->format('Y-m-d');
        $time  = substr($slot->start_time, 0, 5);
        $name  = $this->appointment->doctor->user->name;
        $svc   = $this->appointment->service->name;

        return (new VonageMessage)
            ->content("Vizitas užregistruotas: {$svc} {$date} {$time} pas {$name}.");
    }
}
```

## Email Template Structure (each)

```blade
@component('mail::message')
# {{ config('app.name') }}

<event-specific intro sentence>

**Paslauga:** {{ $appointment->service->name }}
**Gydytojas:** {{ $appointment->doctor->user->name }}
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}

@component('mail::button', ['url' => config('app.url')])
Peržiūrėti vizitą
@endcomponent

Kliniką rasite adresu: {{ config('app.address', '') }}

{{ config('app.name') }} komanda
@endcomponent
```

> `AppointmentRequestedNotification` template omits slot/doctor fields since no slot is assigned at creation time (uses `preferred_date` instead).

## SMS Copy (Lithuanian, ≤ 160 chars)

| Event | Copy |
|---|---|
| Booked | `Vizitas užregistruotas: {Service} {Date} {Time} pas {Doctor}.` |
| Requested | `Jūsų prašymas gautas: {Service} {Date}. Patvirtinsime netrukus.` |
| Confirmed | `Vizitas patvirtintas: {Service} {Date} {Time} pas {Doctor}.` |
| Completed | `Ačiū už apsilankymą! {Service} vizitas baigtas. Laukiame vėl.` |
| Cancelled | `Jūsų vizitas ({Service} {Date}) atšauktas. Susisiekite dėl perkėlimo.` |
| NoShow | `Vizitas {Service} {Date} pažymėtas kaip neatvykimas. Susisiekite.` |
