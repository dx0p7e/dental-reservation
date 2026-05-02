### Requirement: Doctor can view their own appointments only
The system SHALL restrict the doctor's appointments list to appointments where `appointments.doctor_id` matches the authenticated doctor's `Doctor.id`. The list SHALL display: patient name, service name, appointment slot date and time, and status badge. Appointments belonging to other doctors SHALL NOT appear.

#### Scenario: Doctor views their appointment list
- **WHEN** a doctor navigates to the Appointments resource in the doctor panel
- **THEN** only appointments with a `doctor_id` matching their own doctor profile are shown
- **THEN** the table displays columns: patient name, service name, appointment date/time, and status

#### Scenario: Doctor cannot see another doctor's appointments
- **WHEN** the appointment list is rendered for doctor A
- **THEN** no appointments belonging to doctor B are present in the list

### Requirement: Doctor can add notes to an appointment
The system SHALL allow a doctor to open an appointment edit page and save text to the `doctor_notes` field. Patient-identifying fields and booking details SHALL be read-only. The doctor SHALL NOT be able to change the patient, service, slot, or doctor_id.

#### Scenario: Doctor saves a note on an appointment
- **WHEN** a doctor opens an appointment in the doctor panel and enters text in the `doctor_notes` field and saves
- **THEN** the appointment record is updated with the new `doctor_notes` value
- **THEN** no other appointment fields (patient_id, service_id, slot_id, doctor_id) are changed

### Requirement: Doctor can mark an appointment as completed
The system SHALL provide a "Mark Complete" action on the appointment list and edit page. The action SHALL be enabled only when the appointment status is `confirmed`. Triggering the action SHALL set the appointment status to `completed`.

#### Scenario: Doctor marks a confirmed appointment as complete
- **WHEN** a doctor triggers the "Mark Complete" action on a `confirmed` appointment
- **THEN** the appointment status is updated to `completed`

#### Scenario: Mark Complete is not available for non-confirmed appointments
- **WHEN** an appointment has a status other than `confirmed`
- **THEN** the "Mark Complete" action is hidden or disabled

### Requirement: Doctor can mark an appointment as no-show
The system SHALL provide a "Mark No-Show" action on the appointment list and edit page. The action SHALL be enabled only when the appointment status is `confirmed`. Triggering the action SHALL set the appointment status to `no_show`.

#### Scenario: Doctor marks a confirmed appointment as no-show
- **WHEN** a doctor triggers the "Mark No-Show" action on a `confirmed` appointment
- **THEN** the appointment status is updated to `no_show`

#### Scenario: Mark No-Show is not available for non-confirmed appointments
- **WHEN** an appointment has a status other than `confirmed`
- **THEN** the "Mark No-Show" action is hidden or disabled

### Requirement: GET /api/v1/doctors includes services per doctor
The system SHALL include a `services` array in the `DoctorResource` API response. Each entry SHALL contain `{ id, name, price, loyalty_discount_pct }`. The controller SHALL eager-load the `services` relationship to avoid N+1 queries.

#### Scenario: Doctor with assigned services
- **WHEN** a client sends `GET /api/v1/doctors` and doctor 1 has 2 services assigned
- **THEN** the doctor 1 object in the response contains a `services` array with 2 entries

#### Scenario: Doctor with no services assigned
- **WHEN** a client sends `GET /api/v1/doctors` and a doctor has no services
- **THEN** the `services` key in that doctor's response is an empty array `[]`
