## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a page heading, sidebar filters, and a slot card grid
The system SHALL render `DoctorSlotsView.vue` with:
- `<PageHeader title="Book an Appointment" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- A **three-column layout** on `lg` screens and wider (`grid grid-cols-[16rem_1fr_16rem] gap-8`):
  - **Left column**: sticky (`sticky top-6 self-start`) sidebar with service `<select>` and date filter `<input type="date">`
  - **Centre column**: available slot cards grouped by day (see grouping requirement below)
  - **Right column**: sticky (`sticky top-6 self-start`) panel containing the "Continue" button and hint text
- On screens narrower than `lg`, the layout falls back to the existing two-column layout (left sidebar + centre), with the Continue button rendered below the slot grid (not in a third column).
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

## ADDED Requirements

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
