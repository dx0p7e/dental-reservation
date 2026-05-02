## Why

Three runtime bugs confirmed in production after the Change 20 SPA UI redesign deployment, plus two additional defects identified during a full static-analysis pass of all SPA views, stores, and API controllers. All bugs cause visible failures for patients and must be resolved before the next feature work begins.

## What Changes

- **Fix Bug 1 — Booking flow silently redirects away**: `DoctorSlotsView` never sets `bookingStore.selectedDoctor`, so `BookView.onMounted` always evaluates the doctor guard to `true` and pushes to `/`. Fix: fetch and set the doctor from the API in `DoctorSlotsView` using the route `id` param. Also align `canConfirm()` to include the doctor check so the Continue button is only enabled when all three required values are present.
- **Fix Bug 2 — LoyaltyView crashes with "Cannot read properties of undefined (reading 'length')"**: `LoyaltyController` falls back to `new LoyaltyAccount([...])` when no account exists; this model instance has no `transactions` relation loaded, so `LoyaltyResource::whenLoaded` omits the `transactions` key from the JSON. The template accesses `.length` unconditionally and throws. Fix: ensure the fallback account always has an empty transactions relation, and narrow the TypeScript type.
- **Fix Bug 3 — Logout API crashes with "Call to undefined method TransientToken::delete()"**: `AuthController::logout` calls `currentAccessToken()->delete()` which fails when Sanctum returns a `TransientToken` (session-based auth context). Additionally the `auth.ts` store clears local state only *after* the API call, so a 500 response leaves the user stuck in a ghost-authenticated state. Fix: guard the `delete()` call with an `instanceof` check on the backend; clear local state in a `finally` block in the store.
- **Fix Bug 4 — AppointmentsView cancel() has no error handling**: A failed cancellation request is silently swallowed; the patient receives no feedback and the UI shows no error. Fix: wrap in try/catch and surface an inline error message.
- **Fix Bug 5 — BookView back link hardcodes `/doctors/1/slots`**: The "← Back" link always points to doctor 1 regardless of which doctor the booking is for. Fix: derive the correct doctor ID from `bookingStore.selectedDoctor?.id` at render time.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `spa-booking-ui`: Booking flow guard logic changes — `DoctorSlotsView` must set `selectedDoctor` from the API; `canConfirm()` must check all three required store values; `BookView` back link must be dynamic.
- `spa-loyalty-ui`: LoyaltyView `transactions` access must be guarded; `LoyaltyAccount` TypeScript type updated.

## Impact

- `resources/spa/views/DoctorSlotsView.vue` — fetch doctor on mount, update `canConfirm()`
- `resources/spa/views/BookView.vue` — dynamic back link
- `resources/spa/views/AppointmentsView.vue` — error handling in `cancel()`
- `resources/spa/views/LoyaltyView.vue` — guard on `transactions` access
- `resources/spa/types/index.ts` — `LoyaltyAccount.transactions` made optional
- `resources/spa/stores/auth.ts` — `logout()` clears local state in `finally`
- `app/Http/Controllers/Api/V1/AuthController.php` — `logout()` guards `delete()` call
- `app/Http/Controllers/Api/V1/LoyaltyController.php` — fallback account sets empty transactions relation
