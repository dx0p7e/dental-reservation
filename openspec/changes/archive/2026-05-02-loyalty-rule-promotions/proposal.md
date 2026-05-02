## Why

`LoyaltyRule.discount_pct` and `valid_months` have been in the schema since the beginning but are completely unused — the loyalty system only applies tier-based discounts. This is a missed opportunity: the clinic has no way to run time-limited promotional discounts on specific services. Turning `LoyaltyRule` into an active promotion mechanism gives clinic admins a first-class tool for running service-specific promos for loyalty members, with expiry control built in.

## What Changes

- Add `is_active` boolean column to `loyalty_rules` (default `true`) — manual on/off toggle for admins
- Add `UNIQUE(service_id)` constraint to `loyalty_rules` — enforces one rule per service at the DB level
- Wire `LoyaltyRule.discount_pct` into `LoyaltyPricingService::calculate()` as a **multiplicative** promo discount stacked on top of the tier discount — applies only to loyalty members with an active `LoyaltyAccount`
- A rule is "active" when `is_active = true` AND (`valid_months IS NULL` OR `created_at + valid_months months > now()`)
- Expose `promo_discount_pct` alongside `loyalty_discount_pct` in service API responses so the frontend can render both badges separately (Option A: two distinct badges)
- Update Filament `LoyaltyRuleResource` to surface `is_active` as a toggle in the form and table
- Frontend: add a second promo badge in `DoctorSlotsView` and a promo line in `BookView` price breakdown

## Capabilities

### New Capabilities
- none

### Modified Capabilities
- `service-loyalty-discount`: API responses for services now include `promo_discount_pct` (the active rule's discount, or `null`); `loyalty_discount_pct` continues to represent the tier discount unchanged
- `doctor-scoped-services-api`: Same — `promo_discount_pct` added to each service object
- `booking-discount-display`: `DoctorSlotsView` shows a second "X% promo discount" badge; `BookView` shows a promo line in the price breakdown; final price is computed multiplicatively from both discounts
- `spa-booking-ui`: Promo badge added below the tier badge in the service select area

## Impact

- **Migration**: add `is_active` column + unique index on `loyalty_rules`
- **`LoyaltyRule` model**: `is_active` fillable/cast, `scopeActive()` query scope
- **`LoyaltyPricingService`**: updated `calculate()` — promo lookup + multiplicative stacking
- **`LoyaltyPriceResult`**: new `promoDiscountPercent` field; `discountPercent` renamed to `tierDiscountPercent`
- **`ServiceResource` / `ServiceController`**: expose `promo_discount_pct`
- **`DoctorController`**: expose `promo_discount_pct` on doctor-scoped service list
- **`AppointmentController`**: pass `promoDiscountPercent` through to booking preview and store responses
- **`LoyaltyRuleResource`** (Filament): `is_active` toggle in form + table column
- **`DoctorSlotsView.vue`**: second badge for promo
- **`BookView.vue`**: promo line in price breakdown
- **`ServiceSeeder` / `DemoSeeder`**: update seeded rules to set `is_active = true`
