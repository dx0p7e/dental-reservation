## Why

When a patient reaches the booking confirmation step and is blocked by the email/phone verification gate, they navigate away to verify their account. On return, the booking store is empty — they must restart the entire flow from scratch. Persisting the booking draft to localStorage and surfacing a "Continue booking" navbar button eliminates this friction entirely.

## What Changes

- **`useBookingStore`**: Persist `selectedDoctor`, `selectedSlot`, and `selectedService` to localStorage (`booking_draft` key) on every mutation. Rehydrate on store initialisation. Discard stale drafts (slot datetime ≤ current time) automatically.
- **`bookingStore.bookingDraftState`**: New computed returning `null` | `'slot-selection'` | `'confirmation'` based on which fields are set.
- **`bookingStore.clearDraft()`**: Exported method that removes `booking_draft` from localStorage and resets the three store fields.
- **`AppNavbar.vue`**: When authenticated and `bookingDraftState !== null`, show a teal outline "Continue booking" pill between the public links and the user dropdown. Routes to `/doctors/{id}/slots` (slot-selection) or `/dashboard/book` (confirmation).
- **`BookView.vue`**: Call `bookingStore.clearDraft()` after a successful `POST /api/v1/appointments` response.
- **`DoctorSlotsView.vue`**: On mount, if the stored draft's doctor ID matches the route `doctorId` param and the slot is not stale, pre-select the stored slot and service in the UI. If doctor IDs differ, silently clear the draft.
- **TypeScript**: Add `BookingDraft` interface to `resources/spa/types/index.ts`.

## Capabilities

### New Capabilities

- `booking-draft-persistence`: localStorage-backed booking draft — persist, rehydrate, stale-check, and clear; `bookingDraftState` computed; `clearDraft()` method.
- `continue-booking-navbar`: "Continue booking" pill button in `AppNavbar.vue` for authenticated users with an active draft; smart routing based on draft state.

### Modified Capabilities

- `spa-booking-ui`: `DoctorSlotsView` pre-selects stored slot/service on mount if draft matches current doctor; `BookView` clears draft on confirmed booking.

## Impact

- **Frontend store**: `resources/spa/stores/booking.ts` — new persistence logic, computed, and clear method
- **Frontend components**: `resources/spa/components/AppNavbar.vue` (new button), `resources/spa/views/DoctorSlotsView.vue` (draft restore), `resources/spa/views/BookView.vue` (draft clear)
- **TypeScript types**: `resources/spa/types/index.ts` — `BookingDraft` interface added
- **No backend changes** — no new API endpoints, no new database tables
- **No auth changes** — button hidden for guests, no auth guard modifications
