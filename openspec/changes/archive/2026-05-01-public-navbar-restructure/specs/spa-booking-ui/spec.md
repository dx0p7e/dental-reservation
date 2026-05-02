## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a page heading, sidebar filters, and a slot card grid
The system SHALL render `DoctorSlotsView.vue` with:
- `<PageHeader title="Book an Appointment" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- A two-column layout: left sidebar (service `<select>` and doctor filter `<select>`) and a right main area with available slots rendered as clickable cards
- Each slot card: `border rounded-lg p-4` showing date, start time, and doctor name
- Selected slot card: `border-clinic-teal bg-teal-50`
- A "Continue" button enabled only when a slot is selected

On mount, `DoctorSlotsView` SHALL fetch the doctor resource at `GET /api/v1/doctors/:id` and set `bookingStore.selectedDoctor` if it is not already populated, ensuring the booking store is valid regardless of navigation entry point.

The "Continue" button SHALL be disabled (`canConfirm()` returns `false`) unless `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, AND `bookingStore.selectedService` are all non-null.

This route SHALL be accessible without authentication (no `requiresAuth` guard).

#### Scenario: Slot cards are displayed
- **WHEN** a visitor navigates to the booking page
- **THEN** available slot cards are displayed in the right panel with date, time, and doctor name

#### Scenario: Selecting a slot enables Continue
- **WHEN** the patient clicks a slot card
- **THEN** the card receives the selected visual state and the "Continue" button becomes active

#### Scenario: Continue is disabled with no selection
- **WHEN** no slot is selected, or doctor is null
- **THEN** the "Continue" button is visually disabled

#### Scenario: User navigates directly to DoctorSlotsView (no prior LandingView visit)
- **WHEN** a patient navigates directly to `/doctors/1/slots` without passing through LandingView
- **THEN** `DoctorSlotsView` fetches `GET /api/v1/doctors/1` and sets `bookingStore.selectedDoctor`
- **THEN** the Continue button remains disabled until a slot and service are also selected
- **THEN** clicking Continue after selecting slot and service navigates to `/dashboard/book` without redirecting away

#### Scenario: User navigates via LandingView (doctor already in store)
- **WHEN** a patient clicks a doctor card on LandingView (which sets `bookingStore.selectedDoctor`) then arrives at DoctorSlotsView
- **THEN** `DoctorSlotsView` does NOT overwrite the already-set `bookingStore.selectedDoctor`
- **THEN** the booking flow proceeds normally

#### Scenario: Continue button state
- **WHEN** slot is selected but service is not (or doctor is null)
- **THEN** the Continue button is disabled and the hint text "Select a slot and a service to continue." is shown

### Requirement: DoctorSlotsView shows a loyalty discount badge when applicable
The system SHALL display a loyalty discount badge below the service `<select>` when the selected service has a non-zero `loyalty_discount_pct`. The badge SHALL read "{N}% member discount" with `bg-teal-100 text-clinic-teal` styling.

#### Scenario: Loyalty badge appears for discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct > 0`
- **THEN** a teal badge appears below the service select showing the discount percentage (e.g. "5% member discount")

#### Scenario: Loyalty badge hidden for non-discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct` null or 0
- **THEN** no loyalty badge is displayed

### Requirement: DoctorSlotsView shows an empty state with a request-booking link
The system SHALL display an inline empty-state message when no slots are available for the selected service: "No slots available for this service — submit a request instead" with a `<RouterLink>` to `/dashboard/request-appointment?service_id={id}` styled as `text-clinic-blue underline`.

#### Scenario: Empty state shown when no slots
- **WHEN** the slot list for the selected service is empty
- **THEN** the inline empty-state message with the link to `/dashboard/request-appointment` is visible instead of the grid

### Requirement: BookView shows a confirmation card with discount display
The system SHALL render `BookView.vue` as a single centred card (`max-w-lg mx-auto border rounded-lg p-8`) with:
- A back link (`< Back`) above the card
- A "Confirm Booking" `<h1>` heading
- Summary rows: Service name, Doctor name, Date & Time
- Price row: when a discount applies, the original price is shown with `line-through text-clinic-muted` and the final price alongside; "You saved €X" in `text-clinic-teal` below
- A "Confirm Booking" button (`w-full bg-clinic-blue text-white rounded-lg`) at the bottom of the card

`BookView.vue` SHALL be mounted at `/dashboard/book` (authenticated).

#### Scenario: Confirmation card renders booking summary
- **WHEN** the patient arrives at `BookView` with a selected slot in the booking store
- **THEN** service, doctor, date/time, and price are visible in the card

#### Scenario: Discount display shows strikethrough and savings
- **WHEN** the booking has a discount applied
- **THEN** the original price appears with strikethrough, the discounted final price appears next to it, and "You saved €X" appears in teal below

#### Scenario: No discount shows standard price
- **WHEN** the booking has no discount
- **THEN** only the standard price is shown with no strikethrough

### Requirement: BookView confirmation card shows a dynamic back link
The system SHALL render the "← Back" link in `BookView.vue` pointing to `/doctors/{id}/slots` where `{id}` is `bookingStore.selectedDoctor?.id`, with a fallback to `/` if `selectedDoctor` is null.

#### Scenario: Back link uses correct doctor ID
- **WHEN** a patient is on the BookView confirmation page and `bookingStore.selectedDoctor.id` is `3`
- **THEN** the "← Back" link href resolves to `/doctors/3/slots`

#### Scenario: Back link fallback
- **WHEN** `bookingStore.selectedDoctor` is null (defensive case)
- **THEN** the "← Back" link href resolves to `/`
