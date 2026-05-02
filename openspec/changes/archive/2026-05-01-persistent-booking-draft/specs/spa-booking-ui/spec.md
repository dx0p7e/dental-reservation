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

**Draft restore on mount**: If `bookingStore.selectedDoctor?.id` matches the route `doctorId` param AND `bookingStore.selectedSlot` is set (i.e., the store was rehydrated from a non-stale draft), `DoctorSlotsView` SHALL pre-select the stored slot card and the stored service in the `<select>` — so the patient sees their previous selection restored. If the draft's doctor ID does NOT match the route param (user navigated to a different doctor), `bookingStore.clearDraft()` SHALL be called silently.

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

#### Scenario: Draft restored when doctor matches route
- **WHEN** the booking store has a non-stale draft for doctor ID 3 and the route is `/doctors/3/slots`
- **THEN** the previously-selected slot card is highlighted and the previously-selected service is pre-selected in the `<select>`

#### Scenario: Draft cleared when different doctor loaded
- **WHEN** the booking store has a draft for doctor ID 3 but the route is `/doctors/7/slots`
- **THEN** `bookingStore.clearDraft()` is called and the page loads with no pre-selection

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

## MODIFIED Requirements

### Requirement: BookView shows a confirmation card with discount display
The system SHALL render `BookView.vue` as a single centred card (`max-w-lg mx-auto border rounded-lg p-8`) with:
- A back link (`← Back`) above the card
- A "Confirm Booking" `<h1>` heading
- Summary rows: Service name, Doctor name, Date & Time
- Price row: when a discount applies, the original price is shown with `line-through text-clinic-muted` and the final price alongside; "You saved €X" in `text-clinic-teal` below
- A "Confirm Booking" button (`w-full bg-clinic-blue text-white rounded-lg`) at the bottom of the card

`BookView.vue` SHALL be mounted at `/dashboard/book` (authenticated).

After a successful `POST /api/v1/appointments` response, `BookView` SHALL call `bookingStore.clearDraft()` (replacing the previous `bookingStore.reset()` call). This removes the localStorage draft and reactively hides the "Continue booking" navbar button.

#### Scenario: Confirmation card renders booking summary
- **WHEN** the patient arrives at `BookView` with a selected slot in the booking store
- **THEN** service, doctor, date/time, and price are visible in the card

#### Scenario: Discount display shows strikethrough and savings
- **WHEN** the booking has a discount applied
- **THEN** the original price appears with strikethrough, the discounted final price appears next to it, and "You saved €X" appears in teal below

#### Scenario: No discount shows standard price
- **WHEN** the booking has no discount
- **THEN** only the standard price is shown with no strikethrough

#### Scenario: Draft is cleared after confirmed booking
- **WHEN** the patient confirms a booking and `POST /api/v1/appointments` succeeds
- **THEN** `bookingStore.clearDraft()` is called, `localStorage.getItem('booking_draft')` returns `null`, and the "Continue booking" navbar button disappears
