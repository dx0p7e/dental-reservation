# Tasks: Appointment Email Notifications

## 1. Migration

- [ ] 1.1 Run `php artisan make:migration add_reminder_sent_at_to_appointments_table --no-interaction` and add `$table->timestamp('reminder_sent_at')->nullable()->after('doctor_notes')` in `up()` and `$table->dropColumn('reminder_sent_at')` in `down()`
- [ ] 1.2 Run `php artisan migrate --no-interaction` to apply the migration
- [ ] 1.3 Add `'reminder_sent_at'` to `$fillable` and add `'reminder_sent_at' => 'datetime'` cast in `app/Models/Appointment.php`

## 2. Mailables

- [ ] 2.1 Run `php artisan make:mail AppointmentConfirmed --no-interaction`; constructor accepts `Appointment $appointment`; call `->to($appointment->patient->email)`; set `envelope()` subject to "Your appointment is confirmed"; use view `mail.appointment.confirmed`
- [ ] 2.2 Run `php artisan make:mail AppointmentCancelled --no-interaction`; same structure as above; subject "Your appointment has been cancelled"; use view `mail.appointment.cancelled`
- [ ] 2.3 Run `php artisan make:mail AppointmentReminder --no-interaction`; same structure; subject "Reminder: your appointment is tomorrow"; use view `mail.appointment.reminder`
- [ ] 2.4 Eager-load `patient`, `doctor.user`, `service`, and `slot` in each Mailable constructor: `$this->appointment = $appointment->loadMissing('patient', 'doctor.user', 'service', 'slot')`

## 3. Blade Templates

- [ ] 3.1 Create `resources/views/mail/appointment/confirmed.blade.php` — include: greeting with patient name, service name, doctor name (`$appointment->doctor->user->name`), date (`$appointment->slot->date->format('F j, Y')`), time (`$appointment->slot->start_time`), and clinic name from `config('app.name')`
- [ ] 3.2 Create `resources/views/mail/appointment/cancelled.blade.php` — same data as confirmed plus a "please contact us to rebook" closing line
- [ ] 3.3 Create `resources/views/mail/appointment/reminder.blade.php` — reminder phrasing with same appointment data

## 4. Observer

- [ ] 4.1 Add `use App\Mail\AppointmentConfirmed;`, `use App\Mail\AppointmentCancelled;`, `use Illuminate\Support\Facades\Mail;` imports to `AppointmentObserver`
- [ ] 4.2 Add `created(Appointment $appointment): void` method to `AppointmentObserver` — dispatch `Mail::to($appointment->patient->email)->send(new AppointmentConfirmed($appointment))`; eager-load `patient` before dispatching
- [ ] 4.3 In `updated()`, add a branch after the existing `Completed` guard: `if ($appointment->wasChanged('status') && $appointment->status === AppointmentStatus::Cancelled)` → dispatch `AppointmentCancelled`; eager-load `patient` before dispatching

## 5. Reminder Command

- [ ] 5.1 Run `php artisan make:command SendAppointmentReminders --no-interaction`; set `$signature = 'appointments:send-reminders'` and `$description = 'Send reminder emails for appointments starting in ~24 hours'`
- [ ] 5.2 Implement `handle()`: query `Appointment::query()->join('schedule_slots', ...)->where('appointments.status', AppointmentStatus::Confirmed)->whereNull('appointments.reminder_sent_at')->whereRaw("CONCAT(schedule_slots.date, ' ', schedule_slots.start_time) BETWEEN ? AND ?", [now()->addHours(23)->format('Y-m-d H:i:s'), now()->addHours(25)->format('Y-m-d H:i:s')])->select('appointments.*')->with('patient', 'doctor.user', 'service', 'slot')->get()`
- [ ] 5.3 For each result: `Mail::to($appointment->patient->email)->send(new AppointmentReminder($appointment))` then `$appointment->update(['reminder_sent_at' => now()])`

## 6. Scheduler

- [ ] 6.1 Add `Schedule::command('appointments:send-reminders')->hourly()` to `routes/console.php`

## 7. Tests

- [ ] 7.1 Run `php artisan make:test --pest AppointmentEmailNotificationsTest --no-interaction`
- [ ] 7.2 In `setUp` / `beforeEach`, call `Mail::fake()` to prevent actual dispatch
- [ ] 7.3 Test: `AppointmentObserver@created` dispatches `AppointmentConfirmed` to patient email
- [ ] 7.4 Test: `AppointmentObserver@updated` dispatches `AppointmentCancelled` when status → `Cancelled`
- [ ] 7.5 Test: `AppointmentObserver@updated` does NOT dispatch `AppointmentCancelled` when status → `Confirmed`
- [ ] 7.6 Test: `AppointmentObserver@updated` does NOT dispatch any mail when only `notes` changes
- [ ] 7.7 Test: `SendAppointmentReminders` dispatches `AppointmentReminder` for a Confirmed appointment in the 23–25h window with null `reminder_sent_at`
- [ ] 7.8 Test: `SendAppointmentReminders` stamps `reminder_sent_at` after sending
- [ ] 7.9 Test: `SendAppointmentReminders` skips appointments already having `reminder_sent_at`
- [ ] 7.10 Test: `SendAppointmentReminders` skips Pending and Cancelled appointments in the window

## 8. Code Style

- [ ] 8.1 Run `vendor/bin/pint app/Mail/ app/Console/Commands/SendAppointmentReminders.php app/Observers/AppointmentObserver.php app/Models/Appointment.php tests/Feature/AppointmentEmailNotificationsTest.php --format agent`
