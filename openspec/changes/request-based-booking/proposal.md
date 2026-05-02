## Why

The current booking flow only supports self-scheduling — a patient picks a doctor, chooses an available slot, and the appointment is confirmed instantly. This covers the minority of Lithuanian dental clinic interactions; the dominant model (§1.5 of the thesis, OneDoc analysis) is request-based — a patient describes what they need and an admin allocates a time. FR14 specifically requires this flow, and it is not yet implemented.

## What Changes

- **New API endpoint** `POST /api/v1/appointments/request` — authenticated patients submit a service preference, a preferred date (no slot required), and optional notes. Creates an `Appointment` with `status = Pending`, `slot_id = null`, `doctor_id = null`.
- **Schema migration** — `slot_id` and `doctor_id` on `appointments` become nullable to support slot-less, doctor-less request records. A `preferred_date` (`date`, nullable) column is added.
- **Filament Confirm action** — a dedicated inline action on the `AppointmentsTable` for Pending request appointments. Opens a form to assign a doctor, pick a date/time (creates or selects a `ScheduleSlot`), then sets `status = Confirmed`. The existing `AppointmentObserver` fires `AppointmentConfirmed` mail automatically.
- **Patient SPA request form** — a new "Request Appointment" route/view in the Vue SPA where patients submit service + preferred date + notes, distinct from the slot-selection booking flow.
- **No slot conflict check** — admin is trusted to assign a valid time; no double-booking guard on the confirm action.
- **No points impact** — loyalty points are only awarded on `Completed`; this change adds no new point logic.

## Capabilities

### New Capabilities

- `appointment-request-api`: Patient-facing API endpoint to submit a booking request without slot selection
- `admin-confirm-request`: Filament action for admins to confirm a pending request by assigning a doctor and time slot
- `patient-request-booking-ui`: SPA view for patients to submit a request-based booking

### Modified Capabilities

- `appointment-cancellation-email`: No requirement changes — cancellation logic is unaffected by nullable slot/doctor
- `appointment-confirmation-email`: No requirement changes — observer already fires on `status → Confirmed` regardless of how confirmation happens

## Impact

- **Database**: Migration to make `appointments.slot_id` and `appointments.doctor_id` nullable; add `appointments.preferred_date date nullable`
- **Model**: `Appointment` fillable and casts updated; `slot()` and `doctor()` relationships already return `BelongsTo` which handles nullable FKs
- **API**: New `StoreAppointmentRequestRequest` form request; new controller method (or dedicated controller)
- **Filament**: `AppointmentsTable` gains a conditional `Action`; `AppointmentForm` updated to handle nullable slot/doctor on edit
- **SPA**: New Vue view + route; `router.ts` updated
- **Tests**: Existing appointment API tests unaffected (slot-based flow unchanged); new feature tests for request endpoint and guard scenarios
