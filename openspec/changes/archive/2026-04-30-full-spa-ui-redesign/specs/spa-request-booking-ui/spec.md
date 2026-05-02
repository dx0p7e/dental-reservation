## ADDED Requirements

### Requirement: RequestBookingView renders as a centred form card
The system SHALL render `RequestBookingView.vue` as a single centred card (`max-w-lg mx-auto border rounded-lg p-8`) with a `<PageHeader title="Request an Appointment" />` above it. The card SHALL contain:
- A service `<select>` pre-filled from the `service_id` query parameter if present
- A preferred date `<input type="date">`
- A notes `<textarea>` (optional)
- A full-width "Submit Request" button (`w-full bg-clinic-blue text-white rounded-lg`)

#### Scenario: Form card renders with pre-filled service
- **WHEN** the patient navigates to `/request-appointment?service_id=3`
- **THEN** the service select is pre-selected with the service matching ID 3

#### Scenario: Form card renders without pre-fill
- **WHEN** the patient navigates to `/request-appointment` with no query param
- **THEN** the service select defaults to the first option (placeholder or first service)

#### Scenario: Submit request creates an appointment request
- **WHEN** the patient fills in the form and clicks "Submit Request"
- **THEN** the form data is posted to the API and on success the patient is redirected to `/appointments`

#### Scenario: Submit is disabled while required fields are empty
- **WHEN** no service is selected or no preferred date is entered
- **THEN** the "Submit Request" button is disabled
