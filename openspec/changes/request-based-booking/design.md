## Context

The appointments table was designed for the self-scheduling flow: `slot_id` and `doctor_id` are both non-nullable FKs. A patient picks a specific doctor, the system checks slot availability, and the booking is atomic. FR14 requires a second flow where neither a doctor nor a slot is known at submission time — the patient states a preference and waits for admin assignment. The existing `AppointmentObserver` and `AppointmentConfirmed` mailable already handle confirmation emails; no new mail logic is needed.

Current appointment schema (relevant columns):
- `doctor_id` — FK to `doctors`, `NOT NULL`
- `slot_id` — FK to `schedule_slots`, `NOT NULL`
- No `preferred_date` column

## Goals / Non-Goals

**Goals:**
- Patients can submit a booking request with only `service_id`, `preferred_date`, and optional `notes`
- Admins can confirm a request by assigning a doctor and selecting an available slot
- Confirmation triggers the existing `AppointmentConfirmed` email automatically
- Self-scheduled flow is entirely unchanged

**Non-Goals:**
- Double-booking prevention on admin confirm (admin is trusted)
- Doctor assignment at request submission time
- Push notifications or real-time updates
- Differentiating request-based vs self-scheduled in the Filament edit form (read-only distinction is enough)

## Decisions

### 1. Make `slot_id` and `doctor_id` nullable

**Decision:** Add a migration to `ALTER TABLE appointments` — both columns become `nullable()`.

**Rationale:** Request-based appointments start with no slot and no doctor. A `NULL` `slot_id` is the natural discriminator between a request and a self-scheduled appointment throughout the codebase. No new `type` column is needed.

**Alternative considered:** Add a `type ENUM('scheduled','request')` column. Rejected — `slot_id IS NULL` carries the same information without adding redundancy.

### 2. Add `preferred_date date nullable` column

**Decision:** New column on `appointments` to store the patient-stated preferred date.

**Rationale:** Needed for the admin to filter and prioritise requests. Without it, admins have no machine-readable preference signal.

### 3. Admin confirm action assigns an existing `ScheduleSlot`

**Decision:** The Filament confirm action lets the admin pick a doctor, then select from that doctor's available (`is_booked = false`) slots. On confirm: `slot_id` and `doctor_id` are set, `slot.is_booked = true`, `status = Confirmed`.

**Rationale:** Reuses the existing slot booking infrastructure and prevents double-booking at the slot level. Consistent with self-scheduled appointments — both flows end with a `slot_id` assigned.

**Alternative considered:** Add `starts_at`/`ends_at` columns directly to appointments and bypass the slot system on confirm. Rejected — it would create two parallel time-tracking mechanisms in the same table, breaking `AppointmentsTable` columns (`slot.date`, `slot.start_time`) for confirmed requests.

### 4. Separate API endpoint `POST /api/v1/appointments/request`

**Decision:** New route instead of adding a `type` parameter to `POST /api/v1/appointments`.

**Rationale:** The two flows have different validation rules (request needs `preferred_date`, not `slot_id`/`doctor_id`). A dedicated endpoint keeps both form requests clean and avoids conditional validation branches.

### 5. `Appointment` type on `AppointmentResource` (API response)

**Decision:** Extend `AppointmentResource` to make `slot`, `doctor` nullable-safe (already are since they're `BelongsTo`, but ensure the resource returns `null` gracefully).

**Rationale:** Request-based appointments in the patient's appointment list will have `slot: null` and `doctor: null` until confirmed. The SPA `Appointment` type must reflect this.

### 6. Filament action placement

**Decision:** Add a `ConfirmRequestAction` (custom inline action) to `AppointmentsTable`, visible only when `slot_id IS NULL` (i.e. the record is a request). The action opens a modal with doctor select + slot select (filtered dynamically), then saves.

**Rationale:** The existing `EditAction` on every row gives full edit access, but is too broad for the confirm workflow. A dedicated action with a focused modal is clearer for clinic staff.

## Risks / Trade-offs

- **Nullable FK migration** → If any existing query assumes `appointments.slot_id` is always set, it will start returning nulls. Mitigation: audit `AppointmentObserver`, `AppointmentResource`, and `AppointmentApiTest` before migrating. The observer's `updated()` only accesses `patient` and `service`; the resource accesses `slot` and `doctor` — ensure null guards.
- **Slot list in confirm modal** → Fetching all available slots for a doctor via a Filament `Select` with `options()` could be slow for busy schedules. Mitigation: filter `is_booked = false` and limit to future slots; acceptable for MVP.
- **`Appointment` type in SPA** → `Appointment.slot` and `Appointment.doctor` must become nullable in `types/index.ts`. Any component that accesses `appt.slot.date` without a null check will throw. Mitigation: update the type and add null guards in `AppointmentsView`.

## Migration Plan

1. Run migration — `slot_id` and `doctor_id` become nullable, `preferred_date` added.
2. No data migration needed — all existing appointments have valid `slot_id` and `doctor_id`.
3. Rollback: reverse migration re-adds `NOT NULL` constraints (safe as long as no request-based rows exist).
