## ADDED Requirements

### Requirement: AppNavbar shows a "Continue booking" button for authenticated users with an active draft
When `authStore.isAuthenticated` is `true` AND `bookingStore.bookingDraftState !== null`, the system SHALL render a pill-shaped "Continue booking" button in `AppNavbar.vue`. The button SHALL be positioned between the public navigation links and the authenticated user dropdown. It SHALL use a distinct teal outline style (`border border-clinic-teal text-clinic-teal rounded-full px-4 py-1.5 text-sm`) to draw attention without competing with primary actions. The button SHALL NOT be rendered for unauthenticated users.

#### Scenario: Button appears for authenticated user with slot selected
- **WHEN** a patient is authenticated and `bookingStore.bookingDraftState` is `'slot-selection'`
- **THEN** a "Continue booking" button is visible in the navbar

#### Scenario: Button appears for authenticated user at confirmation stage
- **WHEN** a patient is authenticated and `bookingStore.bookingDraftState` is `'confirmation'`
- **THEN** a "Continue booking" button is visible in the navbar

#### Scenario: Button is hidden for guests
- **WHEN** the user is not authenticated (no token)
- **THEN** no "Continue booking" button is rendered in the navbar

#### Scenario: Button is hidden when no draft exists
- **WHEN** the user is authenticated but `bookingStore.bookingDraftState` is `null`
- **THEN** no "Continue booking" button is rendered in the navbar

### Requirement: Continue booking button routes based on draft state
The button SHALL navigate to:
- `/doctors/{selectedDoctor.id}/slots` when `bookingDraftState === 'slot-selection'`
- `/dashboard/book` when `bookingDraftState === 'confirmation'`

#### Scenario: Slot-selection state routes to DoctorSlotsView
- **WHEN** a patient clicks "Continue booking" and `bookingDraftState` is `'slot-selection'` with `selectedDoctor.id = 5`
- **THEN** the router navigates to `/doctors/5/slots`

#### Scenario: Confirmation state routes to BookView
- **WHEN** a patient clicks "Continue booking" and `bookingDraftState` is `'confirmation'`
- **THEN** the router navigates to `/dashboard/book`
