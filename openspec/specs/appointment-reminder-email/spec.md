## ADDED Requirements

### Requirement: Patient receives a reminder email approximately 24 hours before a Confirmed appointment
The system SHALL send a reminder email to the patient approximately 24 hours before their appointment's scheduled start time. The reminder SHALL only be sent for appointments with `Confirmed` status. Each appointment SHALL receive at most one reminder, enforced by a `reminder_sent_at` timestamp on the appointment record.

#### Scenario: Reminder email sent for Confirmed appointment in the 23–25 hour window
- **WHEN** the `appointments:send-reminders` command runs and a Confirmed appointment's slot start falls between 23 and 25 hours from now with `reminder_sent_at` null
- **THEN** a reminder email is dispatched to the patient and `reminder_sent_at` is stamped with the current timestamp

#### Scenario: Reminder not sent twice for the same appointment
- **WHEN** the `appointments:send-reminders` command runs and an appointment already has a non-null `reminder_sent_at`
- **THEN** no reminder email is dispatched for that appointment

#### Scenario: Reminder not sent for Pending or Cancelled appointments
- **WHEN** the `appointments:send-reminders` command runs and an appointment in the 23–25 hour window has status `Pending` or `Cancelled`
- **THEN** no reminder email is dispatched for that appointment

#### Scenario: The reminder command runs hourly via the scheduler
- **WHEN** the Laravel scheduler executes
- **THEN** the `appointments:send-reminders` command is invoked once per hour
