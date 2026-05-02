## ADDED Requirements

### Requirement: Admin can mark a confirmed appointment as no-show

The system SHALL provide an inline action in the admin appointments table that allows an admin to mark a confirmed appointment as no-show.

#### Scenario: NoShow action is visible only for Confirmed appointments

- **GIVEN** the admin is viewing the appointments list
- **WHEN** an appointment has status `Confirmed`
- **THEN** the "No-show" action is visible for that row
- **AND** the action is NOT visible for appointments with any other status

#### Scenario: Admin marks appointment as no-show

- **GIVEN** an appointment with status `Confirmed`
- **WHEN** the admin clicks the "No-show" action and confirms the modal
- **THEN** the appointment's status is updated to `NoShow`
- **AND** no email is sent to the patient

#### Scenario: Admin cancels the no-show confirmation

- **GIVEN** the admin clicks "No-show" on a confirmed appointment
- **WHEN** the admin dismisses the confirmation modal without confirming
- **THEN** the appointment status remains unchanged
