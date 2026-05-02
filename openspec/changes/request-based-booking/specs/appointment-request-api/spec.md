## ADDED Requirements

### Requirement: Authenticated patient can submit a booking request without selecting a slot
The system SHALL expose `POST /api/v1/appointments/request` that accepts `service_id` (required, integer, must exist), `preferred_date` (required, date, must be today or future), and `notes` (optional, string). The endpoint SHALL create an `Appointment` record with `status = Pending`, `slot_id = null`, `doctor_id = null`, and the provided `preferred_date`. It SHALL return the created appointment as an `AppointmentResource` with HTTP 201.

#### Scenario: Patient submits a valid booking request
- **WHEN** an authenticated patient POSTs to `/api/v1/appointments/request` with a valid `service_id`, a future `preferred_date`, and optional `notes`
- **THEN** an `Appointment` is created with `status = pending`, `slot_id = null`, `doctor_id = null`, and the response returns HTTP 201 with the appointment data

#### Scenario: Request with a past preferred_date is rejected
- **WHEN** an authenticated patient POSTs to `/api/v1/appointments/request` with a `preferred_date` in the past
- **THEN** the system returns HTTP 422 with a validation error on `preferred_date`

#### Scenario: Request with a missing service_id is rejected
- **WHEN** an authenticated patient POSTs to `/api/v1/appointments/request` without `service_id`
- **THEN** the system returns HTTP 422 with a validation error on `service_id`

#### Scenario: Unauthenticated request is rejected
- **WHEN** an unauthenticated caller POSTs to `/api/v1/appointments/request`
- **THEN** the system returns HTTP 401

#### Scenario: Request with a non-existent service_id is rejected
- **WHEN** an authenticated patient POSTs to `/api/v1/appointments/request` with a `service_id` that does not exist in the database
- **THEN** the system returns HTTP 422 with a validation error on `service_id`
