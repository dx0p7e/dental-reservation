## Context

**Pricing logic in `store()`** (current):
```php
$account = $request->user()->loyaltyAccount;
$discountPct = 0;
if ($account) {
    $tier = LoyaltyTier::where('tier', $account->tier)->first();
    $discountPct = $tier?->discount_bonus_pct ?? 0;
}
$service = Service::find($request->service_id);
$finalPrice = round($service->price * (1 - $discountPct / 100), 2);
```

**Points logic in `AppointmentObserver::handleCompleted()`**:
```php
$rule = LoyaltyRule::where('service_id', $appointment->service_id)->first();
// awards $rule->points_earned if a rule exists
```

**Key model facts**:
- `LoyaltyTier.discount_bonus_pct` — tier-level discount percentage (used for pricing)
- `LoyaltyRule.points_earned` — per-service points awarded on completion
- `LoyaltyRule.discount_pct` — exists on the model but is NOT used by `store()` (dead field); the service must mirror `store()` and use `LoyaltyTier.discount_bonus_pct` only
- `LoyaltyAccount.points_balance` — current balance; `LoyaltyAccount.tier` — current tier name
- `AppointmentResource` already returns `discount_pct` and `final_price` — frontend just doesn't display them yet

**`BookView.vue` currently** recalculates price client-side from `Service.loyalty_discount_pct` (injected by `ServiceController`) — this will be replaced by the preview API call.

## Goals / Non-Goals

**Goals:**
- Single canonical pricing code path shared by `preview()` and `store()`
- Server-authoritative price shown on confirm screen before the patient commits
- Price context in appointment history and booking emails

**Non-Goals:**
- Changing how points are *awarded* (observer untouched)
- Changing `requestStore()` (those have `final_price = null` by design)
- Removing `loyalty_discount_pct` from `ServiceResource` (it may still be used for client-side badge display pre-slot-selection)

## Decisions

**Decision 1 — `LoyaltyPricingService` + `LoyaltyPriceResult` value object**

Extract pricing into `app/Services/LoyaltyPricingService.php`:
```php
class LoyaltyPricingService
{
    public function calculate(User $patient, Service $service): LoyaltyPriceResult
    {
        $account    = $patient->loyaltyAccount;
        $discountPct = 0;
        if ($account) {
            $tier = LoyaltyTier::where('tier', $account->tier)->first();
            $discountPct = (float) ($tier?->discount_bonus_pct ?? 0);
        }
        $originalPrice  = (float) $service->price;
        $discountAmount = round($originalPrice * $discountPct / 100, 2);
        $finalPrice     = round($originalPrice - $discountAmount, 2);
        $pointsToEarn   = LoyaltyRule::where('service_id', $service->id)->value('points_earned') ?? 0;

        return new LoyaltyPriceResult(
            originalPrice:  $originalPrice,
            discountPercent: $discountPct,
            discountAmount: $discountAmount,
            finalPrice:     $finalPrice,
            pointsToEarn:   $pointsToEarn,
            loyaltyTier:    $account?->tier ?? 'standard',
            pointsBalance:  $account?->points_balance ?? 0,
        );
    }
}
```

`LoyaltyPriceResult` is a readonly PHP 8.2+ class (promoted constructor properties).

*Rationale*: Eliminates duplicated inline logic. Both `store()` and `preview()` call `$this->pricingService->calculate(...)`. If pricing rules change, only one place needs updating.

---

**Decision 2 — Preview endpoint does NOT enforce email/phone verification**

`store()` guards: `if (!hasVerifiedEmail() || !hasVerifiedPhone()) return 403`. The `preview()` endpoint is read-only and non-mutating — it makes no sense to block a patient from *seeing* the price because their phone isn't verified. The confirm button will still be blocked in the UI by the existing verification warning in `BookView.vue`.

*Alternative considered*: Mirror `store()` guards. Rejected — creates confusing UX where the price panel never loads for unverified patients.

---

**Decision 3 — Confirm button disabled until preview resolves**

`BookView.vue` will have a `previewLoaded: boolean` ref. The "Patvirtinti rezervaciją" button is `disabled` when `!previewLoaded`. On preview error, fall back to service base price + note, and re-enable the button (patient can still book; server will calculate the authoritative price).

*Rationale*: The preview is a UX enhancement, not a gate. Failing to fetch preview must not block booking.

---

**Decision 4 — `AppointmentResource` fields `discount_pct` + `final_price` are already present**

No `AppointmentResource` changes needed for the history price display. Only `types/index.ts` (TypeScript interface) and `AppointmentsView.vue` (template) need updating.

---

**Decision 5 — Email templates: conditional price block**

Both `booked.blade.php` and `confirmed.blade.php` use the same `$appointment` variable. Since `final_price` may be `null` for request-based appointments, wrap the price block in `@if($appointment->final_price !== null)`. Show discount line only when `$appointment->discount_pct > 0`.

## Component Map

```
POST /api/v1/appointments/preview
    ↓ auth:sanctum middleware
    ↓ AppointmentController::preview(Request $request)
    ↓ $request->validate([doctor_id, service_id, slot_id])
    ↓ LoyaltyPricingService::calculate($user, $service)
    ↓ LoyaltyPriceResult (value object)
    ↓ return JSON response

POST /api/v1/appointments (store — unchanged outcome)
    ↓ StoreAppointmentRequest
    ↓ DB::transaction
        ↓ LoyaltyPricingService::calculate($user, $service)  ← same code path
        ↓ Appointment::create([..., discount_pct, final_price])
```

```
BookView.vue onMounted
    ↓ POST /api/v1/appointments/preview
    ↓ previewData ref populated
    ↓ template renders: original/final price, discount badge, points preview
    ↓ previewLoaded = true → button enabled
    (on error → fallback text, button re-enabled)
```

## Preview Response Shape

```json
{
  "service_name": "Dantų apžiūra",
  "original_price": "20.00",
  "discount_percent": 10,
  "discount_amount": "2.00",
  "final_price": "18.00",
  "points_to_earn": 18,
  "loyalty_tier": "silver",
  "loyalty_points_balance": 515
}
```

All monetary values are strings formatted to 2 decimal places. `discount_percent` is a plain number (0 when no discount). `points_to_earn` is 0 when no `LoyaltyRule` exists for the service.
