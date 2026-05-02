## ADDED Requirements

### Requirement: Doctor can list their own schedule entries
The system SHALL provide a doctor-authenticated endpoint that returns the doctor's own `DoctorSchedule` rows.

#### Scenario: Doctor lists their schedules
- **WHEN** a doctor sends `GET /api/v1/doctor/schedules`
- **THEN** the response is 200 with all `DoctorSchedule` rows belonging to that doctor

#### Scenario: Non-doctor is rejected
- **WHEN** a non-doctor sends `GET /api/v1/doctor/schedules`
- **THEN** the response is 403

### Requirement: Doctor can create a schedule entry
The system SHALL allow a doctor to create a new `DoctorSchedule` row scoped to themselves. The request MUST include `day_of_week` (integer 1–7), `start_time`, `end_time`, and `slot_duration_minutes` (positive integer).

#### Scenario: Doctor creates a valid schedule entry
- **WHEN** a doctor sends `POST /api/v1/doctor/schedules` with valid fields
- **THEN** the response is 201 with the created schedule and `doctor_id` is set to the doctor's own ID

#### Scenario: Doctor cannot create a schedule for another doctor
- **WHEN** a doctor sends `POST /api/v1/doctor/schedules` with a `doctor_id` pointing to another doctor
- **THEN** the system ignores the supplied `doctor_id` and uses the authenticated doctor's own ID

#### Scenario: Invalid schedule data is rejected
- **WHEN** a doctor sends `POST /api/v1/doctor/schedules` without required fields
- **THEN** the response is 422 with validation errors

### Requirement: Doctor can update their own schedule entry
The system SHALL allow a doctor to update a `DoctorSchedule` row they own.

#### Scenario: Doctor updates their own schedule
- **WHEN** a doctor sends `PUT /api/v1/doctor/schedules/{schedule}` with valid data and the schedule belongs to them
- **THEN** the response is 200 with the updated schedule

#### Scenario: Doctor cannot update another doctor's schedule
- **WHEN** a doctor sends `PUT /api/v1/doctor/schedules/{schedule}` where the schedule belongs to a different doctor
- **THEN** the response is 403

### Requirement: Doctor can delete their own schedule entry
The system SHALL allow a doctor to delete a `DoctorSchedule` row they own.

#### Scenario: Doctor deletes their own schedule
- **WHEN** a doctor sends `DELETE /api/v1/doctor/schedules/{schedule}` and the schedule belongs to them
- **THEN** the response is 204

#### Scenario: Doctor cannot delete another doctor's schedule
- **WHEN** a doctor sends `DELETE /api/v1/doctor/schedules/{schedule}` where the schedule belongs to a different doctor
- **THEN** the response is 403

### Requirement: Schedule management Inertia page loads
The system SHALL render an Inertia page at `/doctor/schedule` showing the doctor's schedule entries with controls to add, edit, and delete rows.

#### Scenario: Schedule page renders for authenticated doctor
- **WHEN** a doctor visits `/doctor/schedule`
- **THEN** an Inertia page is rendered with the doctor's schedule entries listed
