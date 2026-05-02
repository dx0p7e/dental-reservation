## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a page heading, sidebar filters, and a slot card grid
The system SHALL render `DoctorSlotsView.vue` with:
- `<PageHeader title="Book an Appointment" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- A **three-column layout** on `lg` screens and wider (`grid grid-cols-[16rem_1fr_16rem] gap-8`):
  - **Left column**: sticky (`sticky top-20 self-start`) sidebar with service `<select>` and date filter `<input type="date">`
  - **Centre column**: available slot cards grouped by day (see grouping requirement below)
  - **Right column**: sticky (`sticky top-20 self-start`) panel containing the "Continue" button and hint text
- On screens narrower than `lg`, the layout falls back to a two-column layout (left sidebar + centre), with the Continue button rendered below the slot grid (not in a third column).
- Each slot card: `border rounded-lg p-4` showing date, start time, and doctor name
- Selected slot card: `border-clinic-teal bg-teal-50`
- The "Continue" button is enabled only when a slot, service, and doctor are all selected

The service `<select>` SHALL be populated by fetching `GET /api/v1/doctors/{doctor}/services` on mount, showing only services the selected doctor provides. If the doctor has no services assigned, the select SHALL be hidden and an empty-state message SHALL read: "No services available for this doctor. Contact the clinic."

On mount, `DoctorSlotsView` SHALL fetch the doctor resource at `GET /api/v1/doctors/:id` and set `bookingStore.selectedDoctor` if it is not already populated, ensuring the booking store is valid regardless of navigation entry point.

**Draft restore on mount**: If `bookingStore.selectedDoctor?.id` matches the route `doctorId` param AND `bookingStore.selectedSlot` is set (i.e., the store was rehydrated from a non-stale draft), `DoctorSlotsView` SHALL pre-select the stored slot card and the stored service in the `<select>` — so the patient sees their previous selection restored. If the draft's doctor ID does NOT match the route param (user navigated to a different doctor), `bookingStore.clearDraft()` SHALL be called silently.

The "Continue" button SHALL be disabled (`canConfirm()` returns `false`) unless `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, AND `bookingStore.selectedService` are all non-null.

This route SHALL be accessible without authentication (no `requiresAuth` guard).

#### Scenario: Slot cards are displayed
- **WHEN** a visitor navigates to the slots page
- **THEN** available slot cards are displayed in the centre column with date, time, and doctor name

#### Scenario: Three-column layout on large screens
- **WHEN** the viewport is `lg` or wider
- **THEN** the page shows three columns: left sticky sidebar, centre slot grid, right sticky continue panel

#### Scenario: Fallback two-column layout on smaller screens
- **WHEN** the viewport is narrower than `lg`
- **THEN** only the left sidebar and centre grid are shown; the Continue button appears below the grid

#### Scenario: Left sidebar stays visible while scrolling
- **WHEN** the user scrolls down through a long list of slots on a large screen
- **THEN** the service select and date filter remain visible in the left sticky column

#### Scenario: Continue panel stays visible while scrolling
- **WHEN** the user scrolls down through a long list of slots on a large screen
- **THEN** the Continue button remains visible in the right sticky column

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

### Requirement: DoctorSlotsView groups slot cards by date
The system SHALL group available slot cards under labelled date headings in the centre column. Each group SHALL consist of a heading showing the date (ISO format, e.g., `2026-04-12`) followed by a sub-grid of slot cards for that date only (`grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4`). Groups SHALL be rendered in ascending chronological order matching the order returned by the API.

The `groupedSlots` SHALL be implemented as a Vue `computed` property that produces a `Map<string, Slot[]>` keyed by `slot.date`, preserving insertion order.

#### Scenario: Slots split into day groups
- **WHEN** the API returns slots spanning multiple dates (e.g., 2026-04-12 and 2026-04-13)
- **THEN** the centre column shows a date heading for each day followed by only that day's slot cards

#### Scenario: Single day returns one group heading
- **WHEN** all returned slots share the same date (e.g., after filtering by date)
- **THEN** one date heading is shown above the slot grid

#### Scenario: Day group heading is visible
- **WHEN** slot groups are rendered
- **THEN** each group heading displays the ISO date string for that day

### Requirement: DoctorSlotsView shows a loyalty discount badge when applicable
The system SHALL display a loyalty discount badge below the service `<select>` when the selected service has a non-zero `loyalty_discount_pct`. The badge SHALL read "{N}% member discount" with `bg-teal-100 text-clinic-teal` styling.

When the selected service also has a non-null `promo_discount_pct > 0`, a **second badge** SHALL be rendered immediately below the tier badge reading "{N}% promo discount" with `bg-orange-100 text-orange-700` styling. The two badges are independent.

#### Scenario: Loyalty badge appears for discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct > 0`
- **THEN** a teal badge appears below the service select showing the discount percentage (e.g. "5% member discount")

#### Scenario: Loyalty badge hidden for non-discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct` null or 0 AND `promo_discount_pct` null or 0
- **THEN** no badge is displayed

#### Scenario: Promo badge appears when service has an active promotion
- **WHEN** the selected service has `promo_discount_pct > 0`
- **THEN** an orange badge appears showing the promo discount percentage (e.g. "2.5% promo discount")

#### Scenario: Both badges shown simultaneously
- **WHEN** the patient has `loyalty_discount_pct > 0` and the service has `promo_discount_pct > 0`
- **THEN** both the teal member badge and the orange promo badge are visible

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

### Requirement: BookView confirmation card shows a dynamic back link
The system SHALL render the "← Back" link in `BookView.vue` pointing to `/doctors/{id}/slots` where `{id}` is `bookingStore.selectedDoctor?.id`, with a fallback to `/` if `selectedDoctor` is null.

#### Scenario: Back link uses correct doctor ID
- **WHEN** a patient is on the BookView confirmation page and `bookingStore.selectedDoctor.id` is `3`
- **THEN** the "← Back" link href resolves to `/doctors/3/slots`

#### Scenario: Back link fallback
- **WHEN** `bookingStore.selectedDoctor` is null (defensive case)
- **THEN** the "← Back" link href resolves to `/`
