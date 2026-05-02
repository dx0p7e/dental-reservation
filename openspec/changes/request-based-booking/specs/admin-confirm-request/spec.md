## ADDED Requirements

### Requirement: Admin can confirm a pending booking request by assigning a doctor and slot
The Filament `AppointmentsTable` SHALL display a "Confirm" inline action on rows where `slot_id IS NULL` (request-based appointments). The action SHALL open a modal form with: a `doctor_id` select (required, lists active doctors), and a `slot_id` select (required, lists `is_booked = false` slots for the selected doctor, filtered to future dates). On submission the system SHALL set `doctor_id`, `slot_id`, mark the chosen `ScheduleSlot` as `is_booked = true`, and transition the appointment `status` to `Confirmed`. The existing `AppointmentObserver` SHALL fire the `AppointmentConfirmed` mailable automatically.

#### Scenario: Admin confirms a pending request with a valid doctor and slot
- **WHEN** an admin clicks "Confirm" on a pending request appointment, selects a doctor and an available slot, and submits the modal
- **THEN** the appointment `doctor_id` and `slot_id` are set, the slot's `is_booked` becomes `true`, `status` becomes `confirmed`, and a confirmation email is dispatched to the patient

#### Scenario: Confirm action is not visible on self-scheduled appointments
- **WHEN** an admin views the appointments table and a row has a non-null `slot_id` (self-scheduled)
- **THEN** the "Confirm" action is not rendered for that row

#### Scenario: Confirm action is not visible on already-confirmed or cancelled requests
- **WHEN** an appointment with `slot_id = null` has `status = confirmed` or `status = cancelled`
- **THEN** the "Confirm" action is not rendered for that row

#### Scenario: Slot select is filtered to the selected doctor's available slots
- **WHEN** the admin selects a doctor in the confirm modal
- **THEN** the slot select shows only `ScheduleSlot` records where `doctor_id` matches and `is_booked = false` and `date >= today`
