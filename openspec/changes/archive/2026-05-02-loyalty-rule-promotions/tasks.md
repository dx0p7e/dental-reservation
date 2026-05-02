## 1. Migration

- [x] 1.1 Create migration `add_is_active_and_unique_to_loyalty_rules_table` — add `is_active BOOLEAN NOT NULL DEFAULT TRUE` column and `UNIQUE(service_id)` index; handle any duplicate `service_id` rows by keeping the highest `id` per service before adding the constraint

## 2. Model & Scope

- [x] 2.1 Add `is_active` to `LoyaltyRule::$fillable` and `$casts` (`'is_active' => 'boolean'`)
- [x] 2.2 Add `scopeActive(Builder $query): void` to `LoyaltyRule` — filters `is_active = true` AND (`valid_months IS NULL` OR `DATE_ADD(created_at, INTERVAL valid_months MONTH) > NOW()`)

## 3. Pricing Service

- [x] 3.1 Update `LoyaltyPriceResult` — add `promoDiscountPercent: float` constructor parameter
- [x] 3.2 Update `LoyaltyPricingService::calculate()` — if patient has a `LoyaltyAccount`, look up `LoyaltyRule::active()->where('service_id', $service->id)->first()`; compute `$promoDiscountPct = (float) ($rule?->discount_pct ?? 0)`
- [x] 3.3 Update `finalPrice` computation to multiplicative stacking: `round($originalPrice * (1 - $tierDiscountPct/100) * (1 - $promoDiscountPct/100), 2)`
- [x] 3.4 Update `discountAmount` to total saving: `round($originalPrice - $finalPrice, 2)`
- [x] 3.5 Pass `promoDiscountPercent: $promoDiscountPct` to `LoyaltyPriceResult`

## 4. Appointment Controller

- [x] 4.1 Update `AppointmentController::preview()` response to include `promo_discount_percent: $result->promoDiscountPercent`
- [x] 4.2 Verify `store()` — `discount_pct` stored is still `$result->discountPercent` (tier only is fine; combined effective discount is on `final_price`)

## 5. Service API Resources

- [x] 5.1 Update `ServiceController` — after computing `$discountPct`, also look up `LoyaltyRule::active()->where('service_id', $service->id)->value('discount_pct')` for each service and inject as `promo_discount_pct` request attribute
- [x] 5.2 Update `ServiceResource` — add `'promo_discount_pct' => $request->attributes->get('promo_discount_pct')` to the response array
- [x] 5.3 Update `DoctorController` (doctor-scoped services) — inject `promo_discount_pct` per service the same way
- [x] 5.4 Update `DoctorController`'s `ServiceResource` usage to expose `promo_discount_pct` in the doctor services response

## 6. Filament Admin

- [x] 6.1 Add `Toggle::make('is_active')`
- [x] 6.2 Add `IconColumn::make('is_active')->boolean()`
- [x] 6.3 Add `Rule::unique` validation

## 7. Demo Seeder

- [x] 7.1 Update `ServiceSeeder` — add `'is_active' => true` to each `LoyaltyRule::updateOrCreate()` call
- [x] 7.2 Add Teeth Whitening 5% promo (e.g. Teeth Whitening: 5% promo, `valid_months = 3`) so the demo shows both badges

## 8. Frontend — DoctorSlotsView

- [x] 8.1 Add `promo_discount_pct` to `Service` TypeScript interface in `resources/spa/types/index.ts`
- [x] 8.2 Add `promoPct` computed: `selectedService.value?.promo_discount_pct ?? 0`
- [x] 8.3 Add promo badge: `v-if="promoPct > 0"` — `bg-orange-100 text-orange-700` — "{N}% promo discount"

## 9. Frontend — BookView

- [x] 9.1 Update `BookView.vue` to read `promo_discount_percent`
- [x] 9.2 Render promo discount line "X% promo discount" when `promoDiscountPercent > 0`
- [x] 9.3 Savings reflect combined discount

## 10. Tests

- [x] 10.1 Update `LoyaltyPricingService` tests — verify multiplicative stacking when both tier and promo discounts are set
- [x] 10.2 Add test: promo not applied when patient has no `LoyaltyAccount`
- [x] 10.3 Add test: expired rule (`created_at` + `valid_months` in the past) not applied
- [x] 10.4 Add test: `is_active = false` rule not applied even if not expired
- [x] 10.5 Update `ServiceController` / `DoctorController` API tests to assert `promo_discount_pct` is present in responses
- [x] 10.6 Run `php artisan test --compact` and confirm all tests pass

## 11. Code Quality

- [x] 11.1 Run `vendor/bin/pint --dirty --format agent` and fix any style issues
