## ADDED Requirements

### Requirement: Doctor can update the status of their own appointment
The system SHALL allow a doctor to transition a `confirmed` appointment they own to either `completed` or `no_show` via a PATCH request. No other status transitions are permitted through this endpoint.

#### Scenario: Doctor marks appointment as completed
- **WHEN** a doctor sends `PATCH /api/v1/doctor/appointments/{appointment}/status` with `{"status": "completed"}` and the appointment is `confirmed` and belongs to them
- **THEN** the response is 200 and the appointment status is updated to `completed`

#### Scenario: Doctor marks appointment as no_show
- **WHEN** a doctor sends `PATCH /api/v1/doctor/appointments/{appointment}/status` with `{"status": "no_show"}` and the appointment is `confirmed` and belongs to them
- **THEN** the response is 200 and the appointment status is updated to `no_show`

#### Scenario: Invalid status transition is rejected
- **WHEN** a doctor sends `PATCH /api/v1/doctor/appointments/{appointment}/status` with a status other than `completed` or `no_show`
- **THEN** the response is 422

#### Scenario: Transition on non-confirmed appointment is rejected
- **WHEN** a doctor sends `PATCH /api/v1/doctor/appointments/{appointment}/status` on an appointment that is not `confirmed`
- **THEN** the response is 422 with a message indicating the transition is not allowed

#### Scenario: Doctor cannot update another doctor's appointment
- **WHEN** a doctor sends `PATCH /api/v1/doctor/appointments/{appointment}/status` for an appointment that belongs to a different doctor
- **THEN** the response is 403
