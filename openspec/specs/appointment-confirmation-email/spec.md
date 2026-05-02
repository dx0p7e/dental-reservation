## ADDED Requirements

### Requirement: Patient receives a booking confirmation email when an appointment is created
The system SHALL send a transactional email to the patient's registered email address immediately after an appointment record is created. The email SHALL include the service name, doctor full name, appointment date, and appointment start time.

#### Scenario: Confirmation email sent on appointment creation
- **WHEN** a new appointment is created for a patient
- **THEN** a confirmation email is dispatched to `$appointment->patient->email` containing the service name, doctor name, appointment date, and start time

#### Scenario: No confirmation email sent when a non-appointment model is created
- **WHEN** any other model is created (user, slot, etc.)
- **THEN** no appointment confirmation email is dispatched
