## Why

The appointment lifecycle is fully functional, but patients receive no automatic communication. The partial implementation in `appointment-email-notifications` added three Mailables (Confirmed, Cancelled, Reminder) and hooked `AppointmentObserver@created` — but that hook fires with status `Pending` and incorrectly sends a "confirmation" email before any doctor has confirmed the booking. It also covers only two of five status transitions, has no SMS support, and dispatches synchronously with no queue.

FR11 requires automatic notifications via email and/or SMS at every appointment lifecycle event, with the channel controlled by a per-user preference. This change supersedes the partial Mailable approach with a clean Laravel Notification architecture that natively supports multiple channels per class, is fully queued, and covers all six business events.

## What Changes

### 1. `notification_channel` migration on `users`

Add `$table->enum('notification_channel', ['email', 'sms', 'both'])->default('email')` to the `users` table. No UI is exposed in this change — the default is `email` for all users. The profile page change will expose the selector to patients.

### 2. Switch from Mailables to Laravel Notifications

Replace the existing three Mailables (`AppointmentConfirmed`, `AppointmentCancelled`, `AppointmentReminder`) and their synchronous `Mail::to()->send()` calls in `AppointmentObserver` with six Notification classes that implement `ShouldQueue`. Each class's `via()` reads `$notifiable->notification_channel` and returns the correct channel array:

```php
public function via(object $notifiable): array
{
    return match($notifiable->notification_channel) {
        'sms'  => ['vonage'],
        'both' => ['mail', 'vonage'],
        default => ['mail'],
    };
}
```

### 3. Six Notification classes (`app/Notifications/`)

| Class | Trigger |
|---|---|
| `AppointmentBookedNotification` | Patient self-schedules via `store()` (slot assigned, status `Pending`) |
| `AppointmentRequestedNotification` | Patient submits a date-request via `requestStore()` (no slot yet) |
| `AppointmentConfirmedNotification` | Status transition `* → Confirmed` |
| `AppointmentCompletedNotification` | Status transition `* → Completed` |
| `AppointmentCancelledNotification` | Status transition `* → Cancelled` |
| `AppointmentNoShowNotification` | Status transition `* → NoShow` |

Each implements `toMail()` (Blade markdown template) and `toVonage()` (plain-text SMS under 160 characters).

### 4. SMS provider — Vonage

Use `laravel-notification-channels/vonage` (new Composer dependency — requires approval). Requires `VONAGE_KEY`, `VONAGE_SECRET`, and `VONAGE_SMS_FROM` in `.env`. These keys will be added to `.env.example`. Vonage trial credits are sufficient for demo use.

### 5. Observer rewrite for status transitions

`AppointmentObserver` is updated to:
- Remove the existing `created()` hook (creation notifications are now dispatched directly from the controller to distinguish booked vs requested).
- Rewrite `updated()` to dispatch the correct Notification for each status transition: `Confirmed`, `Completed`, `Cancelled`, `NoShow`.
- Retain the existing loyalty-points logic for the `Completed` branch.

### 6. Direct dispatch in `AppointmentController`

`store()` dispatches `AppointmentBookedNotification` after the DB transaction.
`requestStore()` dispatches `AppointmentRequestedNotification` after creation.
This is done outside the observer because the observer's `created()` hook cannot distinguish between these two creation paths.

### 7. Queue — database driver

`jobs` and `failed_jobs` tables already exist. `.env.example` gains `QUEUE_CONNECTION=database`. Demo run via `php artisan queue:work`.

### 8. Blade markdown email templates (`resources/views/notifications/appointments/`)

Six templates — one per event. Each contains: clinic name header, event-specific body paragraph, appointment summary (service, doctor, date/time), and clinic contact footer. Minimal, no images.

### 9. SMS copy (Lithuanian, ≤ 160 chars)

Short per-event strings. Example for booked: `Jūsų vizitas užregistruotas: [Service] [Date] [Time] pas [Doctor]. Klinika: +370XXXXXXXX`

## Capabilities

### New Capabilities

- `appointment-booked-notification`: Patient receives a booking confirmation immediately after self-scheduling a slot.
- `appointment-requested-notification`: Patient receives an acknowledgement when they submit a date-request without a slot.
- `appointment-confirmed-notification`: Patient receives a notification when a doctor or admin confirms the appointment.
- `appointment-completed-notification`: Patient receives a post-visit summary notification on completion.
- `appointment-cancelled-notification`: Patient receives a cancellation notice when status transitions to Cancelled.
- `appointment-no-show-notification`: Patient receives a no-show notice when status transitions to NoShow.
- `notification-channel-preference`: Each user has an `email`, `sms`, or `both` preference that controls which channel each notification is delivered over.

### Modified Capabilities

- `appointment-confirmation-email` (from `appointment-email-notifications`): Replaced by `appointment-booked-notification` + `appointment-confirmed-notification`. The old Mailables are removed.
- `appointment-cancellation-email` (from `appointment-email-notifications`): Replaced by `appointment-cancelled-notification`.
- `appointment-reminder-email` (from `appointment-email-notifications`): The reminder Mailable and `SendAppointmentReminders` command are outside this change's scope and remain unchanged. A follow-up change should convert them to the Notification pattern.

## Impact

- `database/migrations/xxxx_add_notification_channel_to_users_table.php` — new migration
- `app/Models/User.php` — add `notification_channel` to `$fillable`
- `app/Notifications/AppointmentBookedNotification.php` — new
- `app/Notifications/AppointmentRequestedNotification.php` — new
- `app/Notifications/AppointmentConfirmedNotification.php` — new
- `app/Notifications/AppointmentCompletedNotification.php` — new
- `app/Notifications/AppointmentCancelledNotification.php` — new
- `app/Notifications/AppointmentNoShowNotification.php` — new
- `resources/views/notifications/appointments/*.blade.php` — six new templates
- `app/Observers/AppointmentObserver.php` — remove `created()`, rewrite `updated()`
- `app/Http/Controllers/Api/V1/AppointmentController.php` — add notify calls in `store()` and `requestStore()`
- `app/Mail/AppointmentConfirmed.php` — deleted (superseded)
- `app/Mail/AppointmentCancelled.php` — deleted (superseded)
- `.env.example` — add `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM`, `QUEUE_CONNECTION=database`
- `composer.json` — add `laravel-notification-channels/vonage` (new dependency, requires approval)
- No new routes, no SPA changes, Filament panel untouched
