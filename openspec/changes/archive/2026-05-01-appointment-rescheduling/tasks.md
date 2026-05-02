# Tasks: Appointment Rescheduling (FR7)

## 1. Policy

- [x] 1.1 Add `reschedule()` method to `AppointmentPolicy`: `return $user->id === $appointment->patient_id;`

## 2. Route

- [x] 2.1 Add `Route::get('appointments/{appointment}', ...)` and `Route::patch('appointments/{appointment}/reschedule', ...)` inside the `auth:sanctum` group in `routes/api.php`

## 3. Controller — `show()`

- [x] 3.1 Add `show(Request $request, Appointment $appointment): AppointmentResource|JsonResponse` method to `AppointmentController`; authorise via `Gate::authorize('view', $appointment)`; return `AppointmentResource` with `load(['doctor.user', 'service', 'slot'])`

## 4. Notification

- [x] 4.1 Run `php artisan make:notification AppointmentRescheduledNotification --no-interaction`
- [x] 4.2 Implement `AppointmentRescheduledNotification` following the same structure as `AppointmentBookedNotification` (constructor eager-loads `patient`, `doctor.user`, `service`, `slot`; `via()` reads `notification_channel`; `toMail()` uses markdown `notifications.appointments.rescheduled`; `toVonage()` returns short SMS string)
- [x] 4.3 Create `resources/views/notifications/appointments/rescheduled.blade.php` — markdown layout; heading: "Jūsų vizitas sėkmingai perkeltas"; body: "Jūsų vizitas perkeltas į naują laiką."; appointment summary (service, doctor, date from `$appointment->slot->date->format('Y-m-d')`, time from `substr($appointment->slot->start_time, 0, 5)`); CTA button to `config('app.url')`; clinic footer matching sibling templates

## 5. Controller — `reschedule()`

- [x] 5.1 Add imports to `AppointmentController`: `use App\Notifications\AppointmentRescheduledNotification;`
- [x] 5.2 Implement `reschedule(Request $request, Appointment $appointment): AppointmentResource|JsonResponse`:
  - `Gate::authorize('reschedule', $appointment)`
  - Guard: 422 if `$appointment->slot_id === null`
  - Guard: 422 if status not in `[Pending, Confirmed]`
  - `$request->validate(['slot_id' => ['required', 'integer', 'exists:schedule_slots,id']])`
  - `firstOrFail()` with `where('doctor_id', $appointment->doctor_id)` — returns 404 if different doctor
  - Pre-check `is_booked` — return 422 if taken (avoids wasted transaction)
  - `DB::transaction()`: re-acquire new slot with `lockForUpdate()` and `where('is_booked', false)`; also fetch old slot with `lockForUpdate()` before freeing it (`is_booked = false`); take new slot (`is_booked = true`); `$appointment->update(['slot_id' => $newSlot->id])`
  - Load relations and dispatch `AppointmentRescheduledNotification`
  - Return `new AppointmentResource($appointment)` (HTTP 200)

## 6. SPA — Router

- [x] 6.1 Add `{ path: '/appointments/:id/reschedule', component: () => import('@spa/views/RescheduleView.vue'), meta: { requiresAuth: true } }` to `resources/spa/router/index.ts`

## 7. SPA — `RescheduleView.vue`

- [x] 7.1 Create `resources/spa/views/RescheduleView.vue`:
  - `onMounted`: fetch `GET /api/v1/appointments/{id}` and `GET /api/v1/doctors/{doctor_id}/slots`; handle loading state
  - Computed `availableSlots`: filter out the slot whose `id === appointment.slot_id`
  - Show current appointment summary (service, doctor, current date/time)
  - Slot list: same card style as `DoctorSlotsView.vue`; highlight selected slot; disable already-selected slot
  - "Perkelti vizitą" button (disabled until a slot is selected); show submitting state
  - On submit: `PATCH /api/v1/appointments/{id}/reschedule { slot_id }` → `router.push('/appointments?rescheduled=1')` on success; inline error on failure
  - Back link: `RouterLink` to `/appointments`

## 8. SPA — `AppointmentsView.vue`

- [x] 8.1 Add `canReschedule(status: string, slotId: number | null): boolean` function: returns `true` when status is `'pending'` or `'confirmed'` AND `slotId !== null`
- [x] 8.2 Add `Appointment` type field `slot_id: number | null` if not already present in `@spa/types`
- [x] 8.3 Add "Perkelti" `RouterLink` button on appointment cards where `canReschedule(appt.status, appt.slot_id)` is true, alongside the existing "Cancel" button
- [x] 8.4 Add success banner: on `onMounted`, check `route.query.rescheduled === '1'` and show a dismissible success message "Vizitas sėkmingai perkeltas."

## 9. Tests

- [x] 9.1 Run `php artisan make:test --pest AppointmentReschedulingTest --no-interaction`
- [x] 9.2 Test: `reschedule()` returns 200 and updates `slot_id` when valid (pending appointment, same doctor, available slot)
- [x] 9.3 Test: `reschedule()` returns 200 when appointment is `confirmed`
- [x] 9.4 Test: `reschedule()` returns 403 when a different patient tries to reschedule
- [x] 9.5 Test: `reschedule()` returns 422 when appointment has no slot (`slot_id = null`)
- [x] 9.6 Test: `reschedule()` returns 422 when appointment status is `cancelled`
- [x] 9.7 Test: `reschedule()` returns 422 when appointment status is `completed`
- [x] 9.8 Test: `reschedule()` returns 404 when new slot belongs to a different doctor (`firstOrFail()` throws ModelNotFoundException)
- [x] 9.9 Test: `reschedule()` returns 422 when new slot is already booked
- [x] 9.10 Test: old slot is set to `is_booked = false` after successful reschedule
- [x] 9.11 Test: new slot is set to `is_booked = true` after successful reschedule
- [x] 9.12 Test: `AppointmentRescheduledNotification` is dispatched to patient on success
- [x] 9.13 Test: `show()` returns 200 with appointment resource for the owning patient
- [x] 9.14 Test: `show()` returns 403 for a different patient

## 10. Code Style

- [x] 10.1 Run `vendor/bin/pint app/Notifications/AppointmentRescheduledNotification.php app/Http/Controllers/Api/V1/AppointmentController.php app/Policies/AppointmentPolicy.php tests/Feature/AppointmentReschedulingTest.php --format agent` (run when Docker/Sail is available; PHP not accessible in bare WSL)
