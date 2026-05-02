## Why

FR7 requires patients to modify an existing appointment by selecting a new slot. Cancellation already works — rescheduling is the missing half. The slot picker, availability API, and appointment model are all in place; this change adds a reschedule endpoint, frees the old slot, and wires the SPA.

## What Changes

### 1. `PATCH /api/v1/appointments/{appointment}/reschedule`

Single new endpoint. Request body: `{ "slot_id": integer }`.

Business rules enforced:

- Patient can only reschedule their own appointment — policy check
- Appointment must have a slot already assigned (`slot_id` is not null) — requested appointments without a slot cannot be rescheduled
- Appointment must be in `pending` or `confirmed` status — cancelled, completed, no_show cannot be rescheduled
- New slot must exist, belong to the same doctor as the appointment, and have `is_booked = false`
- Old slot is freed: `slot->update(['is_booked' => false])`
- New slot is taken: `new_slot->update(['is_booked' => true])`
- `appointment->slot_id` updated to the new slot
- Status remains unchanged (a confirmed appointment stays confirmed after rescheduling)
- Wrapped in `DB::transaction()` with `lockForUpdate()` on the new slot to prevent double-booking races
- Dispatches `AppointmentRescheduledNotification` to patient after commit

### 2. `AppointmentRescheduledNotification`

New notification class following the Change 23 pattern — `ShouldQueue`, `via()` reads `notification_channel`, implements `toMail()` and `toVonage()`.

- Email body: "Jūsų vizitas perkeltas." + new appointment summary (service, doctor, new date/time)
- SMS copy: `Vizitas perkeltas: {Service} {NewDate} {NewTime} pas {Doctor}.` (≤ 160 chars)
- Eager-loads `patient`, `doctor.user`, `service`, `slot` in constructor

### 3. `AppointmentPolicy` — new `reschedule()` method

```php
public function reschedule(User $user, Appointment $appointment): bool
{
    return $user->id === $appointment->patient_id;
}
```

### 4. `AppointmentController::reschedule()`

New method on the existing controller:

- Authorise via `Gate::authorize('reschedule', $appointment)` (consistent with existing `destroy()`)
- Guard: return 422 if `$appointment->slot_id === null` (unassigned requested appointment)
- Validate status is `pending` or `confirmed` — return 422 otherwise
- Validate new slot belongs to the same doctor — return 422 if different doctor
- Validate new slot is available (`is_booked = false`) with `lockForUpdate()` — return 422 if taken
- Run transaction: free old slot, take new slot, update `appointment->slot_id`
- Dispatch notification
- Return 200 with updated appointment resource

### 5. Route

Inside the existing `auth:sanctum` middleware group:

```php
Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
    ->name('appointments.reschedule');
```

### 6. `RescheduleView.vue`

New SPA view at `/appointments/{id}/reschedule`:

- On mount: fetch `GET /api/v1/appointments/{id}` to get current appointment (doctor, service, slot)
- Fetch available slots for that doctor via `GET /api/v1/doctors/{doctor_id}/slots`
- Patient selects a new slot from the list
- Filter out the currently booked slot client-side
- "Perkelti vizitą" button → `PATCH /api/v1/appointments/{id}/reschedule`
- On success: redirect to `/appointments` with a success banner
- This is a standalone view — it does NOT use or modify `bookingStore` (the booking flow is separate)

### 7. "Perkelti" button in appointment list

In `AppointmentsView.vue`, add a `canReschedule(status, slotId)` function that returns true when `status` is `pending` or `confirmed` **and** `slotId` is not null. Add a "Perkelti" button on qualifying appointment cards, linking to `/appointments/{id}/reschedule`, sitting alongside the existing "Cancel" button.

## Non-Goals

- Admin-initiated reschedule on behalf of a patient
- Rescheduling to a different doctor
- Rescheduling a cancelled or completed appointment
- Rescheduling a requested appointment (no slot assigned yet)
- Waiting list / auto-rescheduling
- Re-running the verification gate on reschedule
