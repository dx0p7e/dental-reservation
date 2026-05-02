## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a page heading, sidebar filters, and a slot card grid
The system SHALL render `DoctorSlotsView.vue` with:
- `<PageHeader title="Book an Appointment" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- A two-column layout: left sidebar (service `<select>` and date filter `<input type="date">`) and a right main area with available slots rendered as clickable cards
- Each slot card: `border rounded-lg p-4` showing date, start time, and doctor name
- Selected slot card: `border-clinic-teal bg-teal-50`
- A "Continue" button enabled only when a slot, service, and doctor are all selected

The service `<select>` SHALL be populated by fetching `GET /api/v1/doctors/{doctor}/services` on mount, showing only services the selected doctor provides. If the doctor has no services assigned, the select SHALL be hidden and an empty-state message SHALL read: "No services available for this doctor. Contact the clinic."

On mount, `DoctorSlotsView` SHALL fetch the doctor resource at `GET /api/v1/doctors/:id` and set `bookingStore.selectedDoctor` if it is not already populated, ensuring the booking store is valid regardless of navigation entry point.

The "Continue" button SHALL be disabled (`canConfirm()` returns `false`) unless `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, AND `bookingStore.selectedService` are all non-null.

This route SHALL be accessible without authentication (no `requiresAuth` guard).

#### Scenario: Slot cards are displayed
- **WHEN** a visitor navigates to the slots page
- **THEN** available slot cards are displayed in the right panel with date, time, and doctor name

#### Scenario: Service select shows only doctor's services
- **WHEN** `DoctorSlotsView` loads for doctor 1 who provides 2 services
- **THEN** the service `<select>` contains exactly those 2 services

#### Scenario: Empty state for doctor with no services
- **WHEN** `DoctorSlotsView` loads for a doctor with no services assigned
- **THEN** the service `<select>` is hidden and the message "No services available for this doctor. Contact the clinic." is displayed

#### Scenario: Selecting a slot enables Continue
- **WHEN** the patient clicks a slot card and a service is already selected
- **THEN** the card receives the selected visual state and the "Continue" button becomes active

#### Scenario: Continue is disabled with no selection
- **WHEN** no slot is selected, or doctor is null, or no service is selected
- **THEN** the "Continue" button is visually disabled

#### Scenario: User navigates directly to DoctorSlotsView (no prior LandingView visit)
- **WHEN** a patient navigates directly to `/doctors/1/slots` without passing through LandingView
- **THEN** `DoctorSlotsView` fetches `GET /api/v1/doctors/1` and sets `bookingStore.selectedDoctor`
- **THEN** the Continue button remains disabled until a slot and service are also selected
- **THEN** clicking Continue after selecting slot and service navigates to `/dashboard/book` without redirecting away

#### Scenario: Continue button state
- **WHEN** slot is selected but service is not (or doctor is null)
- **THEN** the Continue button is disabled and the hint text "Select a slot and a service to continue." is shown
