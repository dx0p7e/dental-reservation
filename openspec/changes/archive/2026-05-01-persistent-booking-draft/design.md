## Context

The booking flow is a multi-step client-side process: `DoctorsView` → `DoctorSlotsView` → `BookView`. State is held exclusively in the Pinia `useBookingStore` (`selectedDoctor`, `selectedSlot`, `selectedService`) as in-memory refs — no persistence. When a patient navigates away mid-flow (e.g., to `/dashboard/profile` to verify email/phone before the verification gate in `BookView` allows them to confirm), the page reload or navigation wipes the store and they must restart.

Key files affected:
- `resources/spa/stores/booking.ts` — currently 17 lines, no persistence
- `resources/spa/views/BookView.vue` — calls `bookingStore.reset()` after successful POST; verification gate checks `profileVerified`
- `resources/spa/views/DoctorSlotsView.vue` — selects slot/service from API response; no draft restore
- `resources/spa/components/AppNavbar.vue` — currently no booking-aware UI
- `resources/spa/types/index.ts` — `Doctor`, `Slot`, `Service` interfaces defined here

## Goals / Non-Goals

**Goals:**
- Persist `{ doctor, slot, service }` to localStorage as `booking_draft` on every store mutation
- Rehydrate the store on initialisation; discard drafts where the stored slot's datetime is in the past
- Expose a `bookingDraftState` computed (`null` | `'slot-selection'` | `'confirmation'`) for UI consumption
- Expose `clearDraft()` to reset the store and remove the localStorage key
- Show a "Continue booking" pill in `AppNavbar.vue` for authenticated users with an active draft
- Pre-restore slot/service selection in `DoctorSlotsView` when the draft doctor matches the route
- Call `clearDraft()` instead of `reset()` in `BookView` after confirmed booking

**Non-Goals:**
- Server-side draft storage
- Multiple concurrent drafts
- Draft expiry notifications
- Recovering drafts after clearing browser storage
- Guest draft persistence (button hidden for unauthenticated users)

## Decisions

### 1. localStorage key: `booking_draft`, stored shape is a slim `BookingDraft` interface
The full `Doctor` object includes `services[]` which is a larger array from the latest change. Storing only the fields the booking flow needs (`id`, `name`, `specialization` for doctor; `id`, `date`, `start_time` for slot; `id`, `name`, `price`, `loyalty_discount_pct` for service) keeps the draft small and avoids stale service list data leaking in.

**Alternative considered**: Store the entire Pinia state. Rejected — the `services[]` array on `Doctor` could grow and contains data not needed for draft restore.

### 2. Staleness check: slot datetime ≤ Date.now()
Draft is considered stale if `new Date(\`${draft.slot.date}T${draft.slot.start_time}\`) <= new Date()`. This is evaluated once on rehydration. No reactive timer — a stale draft is only cleared on the next app initialisation.

**Alternative considered**: Reactive `setInterval` that clears the draft mid-session. Rejected — unnecessary complexity; if the slot has expired the API will reject the booking anyway.

### 3. `bookingDraftState` computed on the store, not in the component
Centralising the draft-state logic in the store means `AppNavbar.vue` and any future consumer has a single, testable source of truth. Three values cover all routing cases without ambiguity.

### 4. Persistence via Pinia `watch` on each ref, not a `$subscribe` plugin
The store uses the Composition API setup() style. Watching `[selectedDoctor, selectedSlot, selectedService]` with `{ deep: true }` in the store body is idiomatic and explicit. On any change, the store either serialises all three to `booking_draft` (if doctor is set) or removes the key (if doctor is cleared, e.g., after `clearDraft()`).

**Alternative considered**: Pinia `pinia-plugin-persistedstate`. Rejected — adds a dependency and stores the entire state object including computed refs, which is harder to control. Manual persistence is 10 lines and fully explicit.

### 5. DoctorSlotsView: draft restore only when doctor IDs match
If the user navigates to a *different* doctor's slots page than the one in the draft, the draft is silently cleared. This avoids pre-selecting a slot from doctor A while the user is now browsing doctor B.

### 6. `clearDraft()` replaces `reset()` in BookView
`clearDraft()` calls `reset()` internally and also removes the localStorage key. BookView currently calls `reset()` — swapping to `clearDraft()` is a one-line change with no behaviour regression for the confirmed-booking case.

## Risks / Trade-offs

- **Clock skew on staleness check**: Device clock set to past would keep a truly-expired draft alive. Risk is negligible — the API will reject booking an expired slot.
- **Draft survives browser tab duplication**: Opening the app in a new tab will rehydrate the same draft. This is desirable (seamless continuation) but could surprise a user who expects tabs to be independent.
- **Pre-selected slot may be gone**: `DoctorSlotsView` restores UI selection state from the draft, but the slot is fetched live from the API. If the slot was taken between drafting and returning, the pre-selected slot won't appear in the grid and `canConfirm()` will remain false. The user must pick a new slot — this is acceptable behaviour.
- **`Doctor` type now includes `services[]`**: The `BookingDraft.doctor` shape intentionally omits `services[]`. The store's `selectedDoctor` ref still holds the full `Doctor` type, so when persisting, the serialiser must pick only the three needed fields.

## Migration Plan

1. Update `useBookingStore` — watch + persist + rehydrate + staleness check + `clearDraft()` + `bookingDraftState`
2. Add `BookingDraft` interface to `types/index.ts`
3. Update `AppNavbar.vue` — conditional pill button
4. Update `BookView.vue` — swap `reset()` → `clearDraft()`
5. Update `DoctorSlotsView.vue` — draft restore on mount
6. No database migrations, no backend changes, no build config changes
7. Rollback: revert the five frontend files; localStorage key `booking_draft` left orphaned but harmless (unknown key, not read)
