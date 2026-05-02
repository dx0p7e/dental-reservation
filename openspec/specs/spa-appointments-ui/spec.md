## MODIFIED Requirements

### Requirement: AppointmentsView renders appointments as cards with status badges
The system SHALL replace the existing HTML table in `AppointmentsView.vue` with a card list where each appointment is rendered as a `border rounded-lg p-4` card containing:
- A `<StatusBadge>` component for the appointment status
- Service name in `font-semibold`
- Date and time from the associated slot, or "Awaiting confirmation" for request-based appointments with no slot
- A "Cancel" button (visible only for `pending` and `confirmed` appointments)

The page SHALL include a `<PageHeader title="My Appointments" />` and action buttons ("Request Appointment" and "Book Appointment") in the top-right area of the heading row.

#### Scenario: Appointments are listed as cards
- **WHEN** the patient navigates to `/dashboard/appointments`
- **THEN** each appointment is displayed as a card with status badge, service name, and date/time

#### Scenario: Pending/confirmed appointment shows Cancel button
- **WHEN** an appointment has status `pending` or `confirmed`
- **THEN** a "Cancel" button is visible on that card

#### Scenario: Completed/cancelled appointment has no Cancel button
- **WHEN** an appointment has status `completed`, `cancelled`, or `no_show`
- **THEN** no "Cancel" button is visible on that card

#### Scenario: Request-based appointment shows awaiting text
- **WHEN** an appointment has no associated slot
- **THEN** "Awaiting confirmation" is shown instead of a date and time

### Requirement: AppointmentsView shows an empty state when no appointments exist
The system SHALL display a centred empty-state message ("No appointments yet") with a "Book your first appointment" link pointing to `/dashboard/book` when the appointment list is empty.

#### Scenario: Empty state when no appointments
- **WHEN** the patient has no appointments
- **THEN** an empty-state message and a booking link to `/dashboard/book` are shown instead of the card list
