## Why

Patients in Silver or Gold loyalty tiers receive a percentage discount on services but currently see no indication of this while booking. Surfacing the discount at the service selection step turns a passive benefit into a visible motivator at the exact moment of decision.

## What Changes

- **`ServiceResource`** — add `loyalty_discount_pct: float|null` field: the authenticated patient's applicable discount percentage, sourced from their `LoyaltyAccount.tier` joined to `LoyaltyTier.discount_bonus_pct`. Guests and patients without a `LoyaltyAccount` receive `null`. Standard-tier patients (0% discount) receive `0.0`.
- **`DoctorSlotsView.vue`** (service selection step) — service `<select>` option label surfaces the discount when `loyalty_discount_pct > 0`; e.g. `"Cleaning (30 min — $80 · 5% member discount)"`.
- **`BookView.vue`** (confirmation step) — when `loyalty_discount_pct > 0`, display the discounted price alongside the original price (original struck through or labelled).
- No price mutation — the `appointments` record continues to store the base price. Discount display is presentational only at this stage.

## Capabilities

### New Capabilities

- `service-loyalty-discount`: The `GET /api/v1/services` response includes a `loyalty_discount_pct` field for each service, reflecting the authenticated patient's tier discount. Guests receive `null`.
- `booking-discount-display`: The booking flow (service selection and confirmation views) visually surfaces the patient's loyalty discount percentage and the resulting effective price.

### Modified Capabilities

*(none — no existing spec requirements change)*

## Impact

- `app/Http/Resources/Api/V1/ServiceResource.php` — add `loyalty_discount_pct` computed field
- `app/Http/Controllers/Api/V1/ServiceController.php` — make route optionally authenticated so `$request->user()` resolves for logged-in patients
- `resources/spa/types/` — extend `Service` type with `loyalty_discount_pct: number | null`
- `resources/spa/views/DoctorSlotsView.vue` — service dropdown renders discount hint
- `resources/spa/views/BookView.vue` — confirmation summary renders discounted price
- No migrations, no new routes, no new models, no dependency changes
