## Context

**Existing app state relevant to this change:**

- `AppointmentController` exists at `app/Http/Controllers/Api/V1/AppointmentController.php` with `store()`, `destroy()`, `requestStore()`. Authorization in `destroy()` uses `Gate::authorize('delete', $appointment)` — use same pattern.
- `AppointmentPolicy` at `app/Policies/AppointmentPolicy.php` has a `before()` guard (admin bypass), `view()`, and `delete()`. The `reschedule()` method must be added.
- `ScheduleSlot` model uses `is_booked` (boolean) — NOT `is_available`. The `store()` method acquires slots with `->where('is_booked', false)->lockForUpdate()` — reschedule must follow the same pattern.
- `Appointment` model: `slot_id` is nullable (requested appointments have no slot). Relationship: `slot()` → `BelongsTo(ScheduleSlot::class, 'slot_id')`. `doctor_id` FK links to `doctors` table.
- `AppointmentStatus` enum: `Pending = 'pending'`, `Confirmed = 'confirmed'`, `Cancelled = 'cancelled'`, `Completed = 'completed'`, `NoShow = 'no_show'`.
- Six `Appointment*Notification` classes exist in `app/Notifications/`. All follow the same pattern: `ShouldQueue`, `Queueable`, `via()` reads `$notifiable->notification_channel`, `toMail()` uses a markdown view, `toVonage()` returns a short string.
- `AppointmentResource` at `app/Http/Resources/Api/V1/AppointmentResource.php` — use for the 200 response.
- `AppointmentsView.vue` cancel button label is "Cancel" (English). "Perkelti" sits alongside it.
- `DoctorSlotsView.vue` is a full page tied to `bookingStore`. `RescheduleView.vue` must be standalone — it knows the doctor from the appointment, does not touch `bookingStore`.
- Router at `resources/spa/router/index.ts` — new route `/appointments/:id/reschedule` must be added with `meta: { requiresAuth: true }`.
- `GET /api/v1/appointments/{id}` — there is no single-appointment show endpoint on the patient API currently. The reschedule view must fetch the appointment from the list endpoint or the show endpoint must be added. Add a `show()` method or use the existing `index()` response shape — simplest is to add `show()`.

## Goals / Non-Goals

**Goals:**
- PATCH reschedule endpoint with policy, status guard, doctor-match guard, slot-availability guard, and transaction
- Free old slot (`is_booked = false`) and lock + take new slot (`is_booked = true`) in one transaction
- `AppointmentRescheduledNotification` queued via same channel pattern as Change 23
- `RescheduleView.vue` standalone SPA page — fetches appointment + doctor's slots, posts reschedule, redirects on success
- "Perkelti" button in `AppointmentsView` for `pending`/`confirmed` appointments that have a slot

**Non-Goals:**
- Admin reschedule, different-doctor reschedule, rescheduling requested appointments

---

## Backend Design

### New route (`routes/api.php`)

Inside the existing `auth:sanctum` group:

```php
Route::get('appointments/{appointment}', [V1AppointmentController::class, 'show'])->name('appointments.show');
Route::patch('appointments/{appointment}/reschedule', [V1AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
```

### `AppointmentPolicy::reschedule()`

```php
public function reschedule(User $user, Appointment $appointment): bool
{
    return $user->id === $appointment->patient_id;
}
```

### `AppointmentController::show()`

```php
public function show(Request $request, Appointment $appointment): AppointmentResource|JsonResponse
{
    Gate::authorize('view', $appointment);

    return new AppointmentResource($appointment->load(['doctor.user', 'service', 'slot']));
}
```

### `AppointmentController::reschedule()`

```php
public function reschedule(Request $request, Appointment $appointment): AppointmentResource|JsonResponse
{
    Gate::authorize('reschedule', $appointment);

    if ($appointment->slot_id === null) {
        return response()->json(['message' => 'This appointment has no assigned slot and cannot be rescheduled.'], 422);
    }

    if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed])) {
        return response()->json(['message' => 'This appointment cannot be rescheduled.'], 422);
    }

    $request->validate(['slot_id' => ['required', 'integer', 'exists:schedule_slots,id']]);

    $newSlot = ScheduleSlot::where('id', $request->slot_id)
        ->where('doctor_id', $appointment->doctor_id)
        ->firstOrFail(); // 404 if different doctor

    if ($newSlot->is_booked) {
        return response()->json(['message' => 'The selected slot is no longer available.'], 422);
    }

    DB::transaction(function () use ($appointment, $newSlot): void {
        // Re-acquire new slot with lock to prevent double-booking races
        $newSlot = ScheduleSlot::where('id', $newSlot->id)
            ->where('is_booked', false)
            ->lockForUpdate()
            ->firstOrFail();

        // Lock old slot before freeing to prevent concurrent reads seeing it as available
        $oldSlot = ScheduleSlot::where('id', $appointment->slot_id)
            ->lockForUpdate()
            ->firstOrFail();

        $oldSlot->update(['is_booked' => false]);
        $newSlot->update(['is_booked' => true]);
        $appointment->update(['slot_id' => $newSlot->id]);
    });

    $appointment->load(['doctor.user', 'service', 'slot', 'patient']);
    $appointment->patient->notify(new AppointmentRescheduledNotification($appointment));

    return new AppointmentResource($appointment);
}
```

### `AppointmentRescheduledNotification`

Follows the exact same structure as `AppointmentBookedNotification`:

```php
public function __construct(public Appointment $appointment)
{
    $this->appointment = $appointment->loadMissing('patient', 'doctor.user', 'service', 'slot');
}
```

- `toMail()`: subject `"Vizitas perkeltas — {app.name}"`, markdown view `notifications.appointments.rescheduled`
- `toVonage()`: `"Vizitas perkeltas: {Service} {Date} {Time} pas {Doctor}."`

### Blade email template

`resources/views/notifications/appointments/rescheduled.blade.php` — markdown layout matching sibling templates:

- Heading: "Jūsų vizitas sėkmingai perkeltas"
- Body: "Jūsų vizitas perkeltas į naują laiką."
- Appointment summary: service, doctor, new date (`$appointment->slot->date->format('Y-m-d')`), new time (`substr($appointment->slot->start_time, 0, 5)`)
- CTA button → `config('app.url')`

---

## Frontend Design

### New route (`resources/spa/router/index.ts`)

```ts
{ path: '/appointments/:id/reschedule', component: () => import('@spa/views/RescheduleView.vue'), meta: { requiresAuth: true } },
```

### `RescheduleView.vue`

```
On mount:
  1. GET /api/v1/appointments/{id}  → current appointment (doctor_id, slot.id, service)
  2. GET /api/v1/doctors/{doctor_id}/slots → available slots

State:
  - appointment: Appointment | null
  - slots: Slot[]
  - selectedSlotId: number | null
  - loading: boolean
  - submitting: boolean
  - error: string

Computed:
  - availableSlots = slots filtered to exclude current slot id

On submit:
  PATCH /api/v1/appointments/{id}/reschedule { slot_id: selectedSlotId }
  → success: router.push('/appointments') with ?rescheduled=1 query param for banner
  → error: show inline error
```

### `AppointmentsView.vue` changes

Add `canReschedule(status, slotId)` helper:

```ts
function canReschedule(status: string, slotId: number | null) {
  return (status === 'pending' || status === 'confirmed') && slotId !== null
}
```

Add "Perkelti" `RouterLink` button alongside "Cancel" on each appointment card where `canReschedule` is true:

```html
<RouterLink
  v-if="canReschedule(appt.status, appt.slot_id)"
  :to="`/appointments/${appt.id}/reschedule`"
  class="rounded-lg border border-clinic-border px-3 py-1 text-xs text-clinic-text hover:bg-clinic-surface"
>
  Perkelti
</RouterLink>
```

Add `rescheduled` query-param success banner logic on mount.
