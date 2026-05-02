## ADDED Requirements

### Requirement: Patient receives a cancellation notice email when their appointment is cancelled
The system SHALL send a transactional email to the patient's registered email address when an appointment's status transitions to `Cancelled`. The email SHALL include the service name, doctor full name, appointment date and start time, and a prompt to contact the clinic to rebook.

#### Scenario: Cancellation email sent when status changes to Cancelled
- **WHEN** an existing appointment's status is updated to `Cancelled`
- **THEN** a cancellation email is dispatched to the patient's email address containing the service name, doctor name, appointment date, start time, and a rebook prompt

#### Scenario: No cancellation email sent when status changes to a non-Cancelled value
- **WHEN** an appointment's status changes to `Confirmed`, `Completed`, or `NoShow`
- **THEN** no cancellation email is dispatched

#### Scenario: No cancellation email sent when a non-status field is updated
- **WHEN** an appointment's `notes` or `doctor_notes` field is updated without a status change
- **THEN** no cancellation email is dispatched
