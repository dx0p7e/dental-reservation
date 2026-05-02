## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a page heading, sidebar filters, and a slot card grid
The system SHALL render `DoctorSlotsView.vue` with:
- `<PageHeader title="Book an Appointment" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- A left sidebar containing a service `<select>` and a date `<input type="date">`
- A slot card grid (right column) showing available slots for the selected doctor
- A loyalty discount badge below the service select when `loyalty_discount_pct > 0`
- An empty-state message with a link to `/request-appointment` when no slots are available

On mount, `DoctorSlotsView` SHALL fetch the doctor resource at `GET /api/v1/doctors/:id` and set `bookingStore.selectedDoctor` if it is not already populated, ensuring the booking store is valid regardless of navigation entry point.

The "Continue" button SHALL be disabled (`canConfirm()` returns `false`) unless `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, AND `bookingStore.selectedService` are all non-null.

#### Scenario: User navigates directly to DoctorSlotsView (no prior LandingView visit)
- **WHEN** a patient navigates directly to `/doctors/1/slots` without passing through LandingView
- **THEN** `DoctorSlotsView` fetches `GET /api/v1/doctors/1` and sets `bookingStore.selectedDoctor`
- **THEN** the Continue button remains disabled until a slot and service are also selected
- **THEN** clicking Continue after selecting slot and service navigates to `/book` without redirecting away

#### Scenario: User navigates via LandingView (doctor already in store)
- **WHEN** a patient clicks a doctor card on LandingView (which sets `bookingStore.selectedDoctor`) then arrives at DoctorSlotsView
- **THEN** `DoctorSlotsView` does NOT overwrite the already-set `bookingStore.selectedDoctor`
- **THEN** the booking flow proceeds normally

#### Scenario: Continue button state
- **WHEN** slot is selected but service is not (or doctor is null)
- **THEN** the Continue button is disabled and the hint text "Select a slot and a service to continue." is shown

### Requirement: BookView confirmation card shows a dynamic back link
The system SHALL render the "← Back" link in `BookView.vue` pointing to `/doctors/{id}/slots` where `{id}` is `bookingStore.selectedDoctor?.id`, with a fallback to `/` if `selectedDoctor` is null.

#### Scenario: Back link uses correct doctor ID
- **WHEN** a patient is on the BookView confirmation page and `bookingStore.selectedDoctor.id` is `3`
- **THEN** the "← Back" link href resolves to `/doctors/3/slots`

#### Scenario: Back link fallback
- **WHEN** `bookingStore.selectedDoctor` is null (defensive case)
- **THEN** the "← Back" link href resolves to `/`
