# Tasks: Loyalty Booking Preview

## 1. `LoyaltyPriceResult` value object

- [x] 1.1 Create `app/Services/LoyaltyPriceResult.php` — readonly class with promoted constructor properties: `float $originalPrice`, `float $discountPercent`, `float $discountAmount`, `float $finalPrice`, `int $pointsToEarn`, `string $loyaltyTier`, `int $pointsBalance`

## 2. `LoyaltyPricingService`

- [x] 2.1 Create `app/Services/LoyaltyPricingService.php` with `calculate(User $patient, Service $service): LoyaltyPriceResult`:
  - Resolve `$account = $patient->loyaltyAccount`
  - Resolve `$discountPct` from `LoyaltyTier::where('tier', $account->tier)->value('discount_bonus_pct') ?? 0`
  - Compute `$originalPrice`, `$discountAmount`, `$finalPrice` (matching existing `store()` formula exactly)
  - Resolve `$pointsToEarn` from `LoyaltyRule::where('service_id', $service->id)->value('points_earned') ?? 0`
  - Return `new LoyaltyPriceResult(...)`

## 3. Refactor `AppointmentController::store()`

- [x] 3.1 Inject `LoyaltyPricingService` via constructor property promotion
- [x] 3.2 Replace inline discount calculation in `store()` with `$result = $this->pricingService->calculate($request->user(), $service)` and use `$result->discountPercent` / `$result->finalPrice`

## 4. `preview()` endpoint

- [x] 4.1 Add `Route::post('appointments/preview', ...)` to `routes/api.php`
- [x] 4.2 Add `preview(Request $request): JsonResponse` to `AppointmentController`:
  - `$request->validate(['doctor_id' => ..., 'service_id' => ..., 'slot_id' => ...])` (same rules as `StoreAppointmentRequest`)
  - No email/phone verification guard
  - `$service = Service::findOrFail($request->service_id)`
  - `$result = $this->pricingService->calculate($request->user(), $service)`
  - Return JSON with all `LoyaltyPriceResult` fields plus `service_name`

## 5. SPA — `BookView.vue`

- [x] 5.1 Add `previewData` ref (nullable typed object), `previewLoading` ref, `previewError` ref
- [x] 5.2 In `onMounted`, call `POST /api/v1/appointments/preview`
- [x] 5.3 Replace the existing client-side `originalPrice`, `discountPct`, `finalPrice`, `savedAmount` computeds with values derived from `previewData` (falling back to `parseFloat(bookingStore.selectedService?.price ?? '0')` when `previewData` is null — matches the existing pattern in the current `originalPrice` computed)
- [x] 5.4 Update the price row in the template:
  - Loading state: animated skeleton
  - Loaded with discount: strikethrough original + final price + discount badge (e.g. `-10% sidabro nuolaida`)
  - Loaded no discount: plain final price
  - Error: base price + note `"Kaina bus apskaičiuota rezervuojant"`
- [x] 5.5 Add a "Taškai už vizitą" row below the price row (visible when `previewData !== null`): `+N tšk.  (likutis po: X tšk.)`
- [x] 5.6 Add a "Jūsų lygis" row: tier name (visible when `previewData !== null` and `previewData.loyalty_tier !== 'standard'`)
- [x] 5.7 Disable "Patvirtinti rezervaciją" button when `previewLoading === true`; re-enable on success or error

## 6. SPA — `AppointmentsView.vue`

- [x] 6.1 Add `discount_pct: number` and `final_price: string | null` fields to the `Appointment` interface in `resources/spa/types/index.ts`
- [x] 6.2 Add a price line to each appointment card in `AppointmentsView.vue`:
  - When `appt.final_price` is null: show nothing
  - When `appt.discount_pct > 0`: show strikethrough `service.price` + green `final_price`
  - When `discount_pct === 0`: show plain `final_price`

## 7. Email templates

- [x] 7.1 Update `resources/views/notifications/appointments/booked.blade.php`:
- [x] 7.2 Update `resources/views/notifications/appointments/confirmed.blade.php` with the same price block

## 8. Tests

- [x] 8.1 Create `tests/Feature/LoyaltyBookingPreviewTest.php` (Pest)
- [x] 8.2 Test: `preview()` returns correct prices and points for a silver-tier patient
- [x] 8.3 Test: `preview()` returns `discount_percent: 0` and full `original_price` for standard tier
- [x] 8.4 Test: `preview()` returns `points_to_earn: 0` when no `LoyaltyRule` exists for service
- [x] 8.5 Test: `preview()` returns 401 when unauthenticated
- [x] 8.6 Test: `preview()` returns 422 when `service_id` is missing
- [x] 8.7 Test: `store()` still produces the correct `discount_pct` and `final_price` after refactor (regression)
- [x] 8.8 Test: `LoyaltyPricingService::calculate()` returns same result as previous inline logic (unit test)

## 9. Code Style

- [x] 9.1 Run `vendor/bin/pint` on all modified PHP files
