# Tasks: Loyalty Discount Visibility During Booking

## 1. Backend — ServiceResource and ServiceController

- [x] 1.1 In `app/Http/Controllers/Api/V1/ServiceController.php`, resolve `auth('sanctum')->user()` before building the resource collection: look up the patient's `LoyaltyAccount.tier` via `LoyaltyAccount::where('patient_id', $user->id)->value('tier')`, then look up `LoyaltyTier::where('tier', $tier)->value('discount_bonus_pct')`. Store result as `$discountPct` (null if unauthenticated or no account). Set it on the request attributes bag via `$request->attributes->set('loyalty_discount_pct', $discountPct)` before returning `ServiceResource::collection(Service::all())`
- [x] 1.2 In `app/Http/Resources/Api/V1/ServiceResource.php`, add `'loyalty_discount_pct' => $request->attributes->get('loyalty_discount_pct')` to the `toArray()` array

## 2. Frontend — TypeScript Type

- [x] 2.1 In `resources/spa/types/index.ts`, add `loyalty_discount_pct: number | null` to the `Service` interface

## 3. Frontend — DoctorSlotsView Service Selector

- [x] 3.1 In `resources/spa/views/DoctorSlotsView.vue`, update the service `<option>` label to append a discount hint when `service.loyalty_discount_pct` is greater than 0. Format: `"{{ service.name }} ({{ service.duration_minutes }} min — {{ service.price }}{{ service.loyalty_discount_pct > 0 ? ` · ${service.loyalty_discount_pct}% member discount` : '' }})"`

## 4. Frontend — BookView Confirmation Summary

- [x] 4.1 In `resources/spa/views/BookView.vue`, add a "Price" row to the summary block. When `bookingStore.selectedService?.loyalty_discount_pct` is greater than 0, display the original price with a strikethrough and a computed discounted price below it. Compute: `Math.round(parseFloat(service.price) * (1 - discount_pct / 100) * 100) / 100`
- [x] 4.2 When `loyalty_discount_pct` is null or 0, display only the base price with no additional discount row

## 5. Tests

- [x] 5.1 Run `php artisan make:test --pest ServiceLoyaltyDiscountTest --no-interaction`
- [x] 5.2 Test: unauthenticated `GET /api/v1/services` returns `loyalty_discount_pct: null` for each service
- [x] 5.3 Test: authenticated patient with a Silver-tier `LoyaltyAccount` receives `loyalty_discount_pct: 5.0`
- [x] 5.4 Test: authenticated patient with a Gold-tier `LoyaltyAccount` receives `loyalty_discount_pct: 10.0`
- [x] 5.5 Test: authenticated patient with a Standard-tier `LoyaltyAccount` receives `loyalty_discount_pct: 0.0`
- [x] 5.6 Test: authenticated patient with no `LoyaltyAccount` receives `loyalty_discount_pct: null`

## 6. Code Style

- [x] 6.1 Run `vendor/bin/pint app/Http/Resources/Api/V1/ServiceResource.php app/Http/Controllers/Api/V1/ServiceController.php tests/Feature/ServiceLoyaltyDiscountTest.php --format agent`
