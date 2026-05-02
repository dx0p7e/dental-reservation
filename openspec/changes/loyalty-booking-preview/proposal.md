## Why

The thesis claims real-time loyalty status visibility during booking is the system's core differentiator. The discount is calculated and stored correctly server-side, but the booking confirm screen shows a flat price with no indication of tier, discount applied, or points to be earned. `final_price` is in every appointment API response but is never displayed anywhere in the SPA. Additionally, `BookView.vue` recalculates price client-side using `loyalty_discount_pct` from the services endpoint — a trust boundary where the client re-implements server pricing logic without any server confirmation of what the actual charge will be.

This change closes all three gaps: shows authoritative pricing at confirm time, surfaces `final_price` in appointment history, and adds pricing context to booking notification emails.

## What Changes

1. **`POST /api/v1/appointments/preview`** — new read-only endpoint. Accepts the same body as `store()` (`doctor_id`, `service_id`, `slot_id`). Returns an authoritative price breakdown: original price, discount percent, discount amount, final price, points to be earned, current tier, and current points balance. Does not create an appointment. Auth-guarded (requires authenticated patient).

2. **`LoyaltyPricingService`** — new service class at `app/Services/LoyaltyPricingService.php` with a `calculate(User $patient, Service $service): LoyaltyPriceResult` method. Extracts the discount + points logic currently inline in `AppointmentController::store()`. Both `preview()` and `store()` call this, eliminating the duplicate pricing path and the client-side trust boundary.

3. **`BookView.vue` confirm step** — calls `POST /api/v1/appointments/preview` on mount. Shows a loading skeleton while the preview loads. Replaces the flat price line with a rich breakdown: original price (struck through if discounted), final price, discount badge, points to be earned, and points balance after. Falls back to service base price with an explanatory note if the preview call fails. Confirm button is disabled until preview resolves successfully.

4. **`AppointmentsView.vue` price display** — adds a price line to each appointment card using `final_price` from the existing API response. Shows strikethrough original + green final price when `discount_pct > 0`, plain price otherwise.

5. **Booking notification emails** — updates `booked.blade.php` and `confirmed.blade.php` to include a price line. When `discount_pct > 0`, shows both original and final price with discount label. When no discount, shows `final_price` plainly.

## Capabilities

### New Capabilities

- `loyalty-booking-preview`: Read-only pricing preview endpoint for authenticated patients before confirming a booking

### Modified Capabilities

- `booking-api`: `store()` refactored to delegate to `LoyaltyPricingService`; new `preview()` method added
- `patient-spa`: Confirm step enriched with server-authorised price breakdown and points preview
- `appointment-email-notifications`: Booking and confirmation email templates updated to show price

## Impact

- `app/Services/LoyaltyPricingService.php` — new file
- `app/Services/LoyaltyPriceResult.php` — new value object
- `app/Http/Controllers/Api/V1/AppointmentController.php` — refactored `store()`, new `preview()`
- `routes/api.php` — one new POST route
- `resources/spa/views/BookView.vue` — confirm step enriched
- `resources/spa/views/AppointmentsView.vue` — price display added
- `resources/spa/types/index.ts` — `Appointment` type updated with `discount_pct` and `final_price`
- `resources/views/notifications/appointments/booked.blade.php` — price added
- `resources/views/notifications/appointments/confirmed.blade.php` — price added

## Non-Goals

- Manual points redemption
- Loyalty programme configuration UI
- Retroactive `final_price` correction on old appointments
- Changing the points-award-on-completion flow (AppointmentObserver untouched)
- Request-based appointment pricing (those have `final_price = null` by design)
