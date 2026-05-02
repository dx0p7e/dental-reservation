# Tasks: Email & SMS Notifications with Channel Preference

## 0. Dependency (requires approval)

- [x] 0.1 Get approval for new Composer dependency, then run `composer require laravel/vonage-notification-channel --no-interaction` (package name corrected from proposal — the community `laravel-notification-channels/vonage` no longer exists; `laravel/vonage-notification-channel` v3.3.4 is the official first-party package; `VonageMessage` is at `Illuminate\Notifications\Messages\VonageMessage`)
- [ ] 0.2 Confirm `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM` values are available for `.env` (add to `.env` manually — `.env.example` is updated in task 2.1)

## 1. Migration — `notification_channel` on `users`

- [x] 1.1 Run `php artisan make:migration add_notification_channel_to_users_table --no-interaction` and add `$table->enum('notification_channel', ['email', 'sms', 'both'])->default('email')->after('phone')` in `up()` and `$table->dropColumn('notification_channel')` in `down()`
- [x] 1.2 Run `php artisan migrate --no-interaction`
- [x] 1.3 Add `'notification_channel'` to `$fillable` in `app/Models/User.php`

## 2. Environment

- [x] 2.1 Add to `.env.example`:
  ```
  VONAGE_KEY=
  VONAGE_SECRET=
  VONAGE_SMS_FROM=
  QUEUE_CONNECTION=database
  ```

## 3. Notification Classes

- [x] 3.1 Create `app/Notifications/AppointmentBookedNotification.php` — constructor accepts `Appointment $appointment`; implements `ShouldQueue`; `via()` reads `$notifiable->notification_channel`; `toMail()` uses markdown view `notifications.appointments.booked`; `toVonage()` returns SMS copy; eager-load `patient`, `doctor.user`, `service`, `slot` in constructor: `$this->appointment = $appointment->loadMissing('patient', 'doctor.user', 'service', 'slot')`
- [x] 3.2 Create `app/Notifications/AppointmentRequestedNotification.php` — same structure; `toMail()` uses `notifications.appointments.requested`; `toVonage()` SMS uses `$this->appointment->preferred_date->format('Y-m-d')` (confirmed column name from migration `2026_04_30_135712_…`, cast to `date` on the model); eager-load `patient`, `service` only (no slot/doctor yet)
- [x] 3.3 Create `app/Notifications/AppointmentConfirmedNotification.php` — same structure; `toMail()` uses `notifications.appointments.confirmed`; eager-load `patient`, `doctor.user`, `service`, `slot`
- [x] 3.4 Create `app/Notifications/AppointmentCompletedNotification.php` — same structure; `toMail()` uses `notifications.appointments.completed`; eager-load `patient`, `doctor.user`, `service`, `slot` (doctor name appears in the full appointment summary in both `toMail()` and `toVonage()`)
- [x] 3.5 Create `app/Notifications/AppointmentCancelledNotification.php` — same structure; `toMail()` uses `notifications.appointments.cancelled`; eager-load `patient`, `service`, `slot`
- [x] 3.6 Create `app/Notifications/AppointmentNoShowNotification.php` — same structure; `toMail()` uses `notifications.appointments.no-show`; eager-load `patient`, `service`, `slot`

## 4. Blade Email Templates

- [x] 4.1 Create `resources/views/notifications/appointments/booked.blade.php` — markdown layout; include: clinic name, "Jūsų vizitas sėkmingai užregistruotas" heading, appointment summary (service, doctor, date from `$appointment->slot->date->format('Y-m-d')`, time from `substr($appointment->slot->start_time, 0, 5)`), CTA button to `config('app.url')`, clinic footer
- [x] 4.2 Create `resources/views/notifications/appointments/requested.blade.php` — same structure but no slot/doctor fields; show `preferred_date` instead; body text: "Jūsų vizito prašymas gautas. Netrukus patvirtinsime laiką."
- [x] 4.3 Create `resources/views/notifications/appointments/confirmed.blade.php` — body text: "Jūsų vizitas patvirtintas."; full appointment summary
- [x] 4.4 Create `resources/views/notifications/appointments/completed.blade.php` — body text: "Ačiū už apsilankymą! Tikimės matyti jus vėl."; full appointment summary
- [x] 4.5 Create `resources/views/notifications/appointments/cancelled.blade.php` — body text: "Jūsų vizitas atšauktas. Susisiekite su mumis dėl naujo laiko."; appointment summary
- [x] 4.6 Create `resources/views/notifications/appointments/no-show.blade.php` — body text: "Pažymėta, kad neatvykote į vizitą. Susisiekite su mumis."; appointment summary

## 5. Observer — Rewrite

- [x] 5.1 Remove `created()` method from `AppointmentObserver` (notifications for creation events now dispatched from controller)
- [x] 5.2 Remove `use App\Mail\AppointmentConfirmed;`, `use App\Mail\AppointmentCancelled;`, and `use Illuminate\Support\Facades\Mail;` imports
- [x] 5.3 Add imports: `use App\Notifications\AppointmentConfirmedNotification;`, `use App\Notifications\AppointmentCompletedNotification;`, `use App\Notifications\AppointmentCancelledNotification;`, `use App\Notifications\AppointmentNoShowNotification;`, `use App\Enums\AppointmentStatus;` (already imported if retained from existing observer — verify and add only if missing)
- [x] 5.4 Rewrite `updated()` method using `match($appointment->status)` with enum cases — **do NOT use bare strings; `$appointment->status` is an `AppointmentStatus` enum instance** (cast in the model):
  - Guard: `if (! $appointment->wasChanged('status')) { return; }`
  - Load patient: `$appointment->loadMissing('patient')`
  - `match($appointment->status) { AppointmentStatus::Confirmed => ..., AppointmentStatus::Cancelled => ..., AppointmentStatus::NoShow => ..., AppointmentStatus::Completed => ..., default => null }`
    - `AppointmentStatus::Confirmed` → `$appointment->patient->notify(new AppointmentConfirmedNotification($appointment))`
    - `AppointmentStatus::Cancelled` → `$appointment->patient->notify(new AppointmentCancelledNotification($appointment))`
    - `AppointmentStatus::NoShow` → `$appointment->patient->notify(new AppointmentNoShowNotification($appointment))`
    - `AppointmentStatus::Completed` → `$appointment->patient->notify(new AppointmentCompletedNotification($appointment))` + existing loyalty-points logic (unchanged)
    - `default` → return (no notification for `Pending` or other transitions)

## 6. Controller — Creation Dispatch

- [x] 6.1 Add `use App\Notifications\AppointmentBookedNotification;` and `use App\Notifications\AppointmentRequestedNotification;` imports to `AppointmentController`
- [x] 6.2 In `store()`, after `$appointment->load([...])`, add notify call for `AppointmentBookedNotification`
- [x] 6.3 In `requestStore()`, after `$appointment->load(['service'])`, add notify call for `AppointmentRequestedNotification`

## 7. Delete Superseded Mailables

- [x] 7.1 Delete `app/Mail/AppointmentConfirmed.php`
- [x] 7.2 Delete `app/Mail/AppointmentCancelled.php`

> **Note:** `app/Mail/AppointmentReminder.php` and `app/Console/Commands/SendAppointmentReminders.php` are NOT deleted — they belong to the reminder feature which is outside this change's scope.

## 8. Tests

- [x] 8.1 Run `php artisan make:test --pest EmailSmsNotificationsTest --no-interaction`
- [x] 8.2 In `beforeEach`, call `Notification::fake()` to prevent real dispatch
- [x] 8.3 Test: `store()` dispatches `AppointmentBookedNotification` to the patient
- [x] 8.4 Test: `requestStore()` dispatches `AppointmentRequestedNotification` to the patient
- [x] 8.5 Test: observer dispatches `AppointmentConfirmedNotification` when status → `Confirmed`
- [x] 8.6 Test: observer dispatches `AppointmentCompletedNotification` when status → `Completed`
- [x] 8.7 Test: observer dispatches `AppointmentCancelledNotification` when status → `Cancelled`
- [x] 8.8 Test: observer dispatches `AppointmentNoShowNotification` when status → `NoShow`
- [x] 8.9 Test: observer does NOT dispatch any notification when a non-status field changes (e.g., `notes`)
- [x] 8.10 Test: `via()` returns `['mail']` for `notification_channel = 'email'`
- [x] 8.11 Test: `via()` returns `['vonage']` for `notification_channel = 'sms'`
- [x] 8.12 Test: `via()` returns `['mail', 'vonage']` for `notification_channel = 'both'`

## 9. Code Style

- [x] 9.1 Run `vendor/bin/pint app/Notifications/ app/Observers/AppointmentObserver.php app/Http/Controllers/Api/V1/AppointmentController.php app/Models/User.php tests/Feature/EmailSmsNotificationsTest.php --format agent`
