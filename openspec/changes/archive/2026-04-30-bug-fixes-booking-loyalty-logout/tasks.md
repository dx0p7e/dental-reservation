## 1. Bug 1 — Booking Flow Redirect Fix (DoctorSlotsView + BookView)

- [x] 1.1 In `DoctorSlotsView.vue`, add a `fetchDoctor()` function that calls `GET /api/v1/doctors/:id` (using the `doctorId` route param) and sets `bookingStore.selectedDoctor` with the result
- [x] 1.2 In `DoctorSlotsView.vue onMounted`, call `fetchDoctor()` inside the `Promise.all` if `bookingStore.selectedDoctor` is null, so it runs in parallel with the existing slot and service fetches
- [x] 1.3 Update `canConfirm()` in `DoctorSlotsView.vue` to check `bookingStore.selectedDoctor !== null` in addition to slot and service
- [x] 1.4 In `BookView.vue`, replace the hardcoded `to="/doctors/1/slots"` back link with a computed `backLink` that resolves to `/doctors/{bookingStore.selectedDoctor?.id}/slots` when `selectedDoctor` is set, or `/` as a fallback

## 2. Bug 2 — LoyaltyView Crash Fix (Backend + Frontend)

- [x] 2.1 In `LoyaltyController::show`, after creating the fallback `new LoyaltyAccount([...])`, call `$account->setRelation('transactions', collect([]))` so `whenLoaded` always finds the relation
- [x] 2.2 In `resources/spa/types/index.ts`, change `transactions: LoyaltyTransaction[]` to `transactions?: LoyaltyTransaction[]` in the `LoyaltyAccount` interface
- [x] 2.3 In `LoyaltyView.vue`, change all template accesses to `loyalty.transactions` to use `loyalty.transactions ?? []` (affects: `v-if="loyalty.transactions.length === 0"` and `v-for="tx in loyalty.transactions"`)

## 3. Bug 3 — Logout TransientToken Fix (Backend + Frontend)

- [x] 3.1 In `AuthController::logout`, wrap `$request->user()->currentAccessToken()->delete()` with an `instanceof \Laravel\Sanctum\PersonalAccessToken` check so the `delete()` is only called for real token instances
- [x] 3.2 In `AuthController::logout`, after the token-delete block, add `Auth::guard('web')->logout()`, `$request->session()->invalidate()`, and `$request->session()->regenerateToken()` to clean up any session-based auth state
- [x] 3.3 In `resources/spa/stores/auth.ts`, move `token.value = null; user.value = null; localStorage.removeItem('booking_token')` into a `finally` block so local state is always cleared even if the API call throws

## 4. Bug 4 — AppointmentsView Cancel Error Handling

- [x] 4.1 In `AppointmentsView.vue`, add a `cancelError` ref and a `cancelling` ref (for per-card loading state)
- [x] 4.2 Wrap the `api.delete()` call in `cancel()` with try/catch; on error, set `cancelError` to the API message; call `fetchAppointments()` only on success
- [x] 4.3 Render an inline error message in the template when `cancelError` is non-empty (near the appointment list, dismissible)

## 5. Bug 5 — BookView Back Link

Already covered in task 1.4.

## 6. Tests

- [x] 6.1 Write a Pest feature test for `AuthController::logout` covering: (a) successful logout with a valid Sanctum token returns 204, (b) request authenticated via session guard does not throw a fatal error and returns 204
- [x] 6.2 Write a Pest feature test for `LoyaltyController::show` verifying that a user with no loyalty account receives a 200 response with `"transactions": []` in the payload
- [x] 6.3 Run `php artisan test --compact` and confirm all tests pass
