## Context

`LoyaltyRule` has two unused fields — `discount_pct` and `valid_months` — and no uniqueness constraint on `service_id`. `LoyaltyPricingService::calculate()` is the single pricing choke-point that computes tier discounts and `LoyaltyPriceResult`. Currently `discountPercent` on that result represents only the tier discount. The frontend receives a single `loyalty_discount_pct` per service.

**Decisions from explore session:**
- Promo applies to **loyalty members only** (patient must have a `LoyaltyAccount`)
- One active rule per service maximum (UNIQUE constraint)
- Two separate badges in the UI (Option A)
- Manual `is_active` toggle in addition to time-based `valid_months` expiry

## Goals / Non-Goals

**Goals:**
- Make `LoyaltyRule.discount_pct` a live, applied promo discount stacked multiplicatively on the tier discount
- `is_active` + `valid_months` control when a rule is applied
- API exposes both discounts separately (`loyalty_discount_pct`, `promo_discount_pct`)
- Frontend renders them as two distinct badges
- Filament admin surfaces `is_active`

**Non-Goals:**
- Pre-scheduling promos (start date) — `valid_months` window begins at `created_at`
- Promo discounts for non-members / unauthenticated users
- Multiple simultaneous active rules per service

## Decisions

### Unique constraint on `service_id`

**Decision:** Add `UNIQUE(service_id)` to `loyalty_rules` via migration.

**Rationale:** The intent is one configurable rule per service. DB-level enforcement is more reliable than application-level guards. The Filament resource should also handle the unique violation gracefully (the `service_id` select should ideally hide services that already have a rule).

**Alternative considered:** Application-level validation only — rejected because it leaves a window for concurrent creates.

### `is_active` column

**Decision:** `is_active BOOLEAN NOT NULL DEFAULT TRUE` — added to `loyalty_rules`.

**Rationale:** Allows admin to manually pause a rule without deleting it (pre-configure, flip when ready). Combined with `valid_months`, the full activity logic is:

```
rule is applied when:
  is_active = TRUE
  AND (valid_months IS NULL OR DATE_ADD(created_at, INTERVAL valid_months MONTH) > NOW())
```

This is encapsulated in `LoyaltyRule::scopeActive(Builder $query): void`.

### Multiplicative discount stacking

**Decision:** `final = price × (1 − tier%) × (1 − promo%)`

**Rationale:** Promo is applied to the post-tier price ("X% off what you'd already pay as a member"). Avoids additive stacking exceeding 100% for very high values. Consistent with how compound discounts are typically presented.

**Alternative considered:** Additive (`tier% + promo%`) — rejected; harder to reason about, breaks for high-discount combinations.

### `LoyaltyPriceResult` field addition (not rename)

**Decision:** Add `promoDiscountPercent: float` to `LoyaltyPriceResult`. Keep `discountPercent` as-is (tier discount) to avoid breaking existing callers.

**Rationale:** `discountPercent` is referenced in `AppointmentController` twice — renaming risks breakage. Adding a new field is additive and safe.

**`discountAmount` stays combined:** The `discountAmount` field (used in BookView price breakdown) will continue to represent the total monetary saving so the "You saved €X" line remains accurate.

### `finalPrice` computation

```php
$tierFactor  = 1 - $tierDiscountPct / 100;
$promoFactor = 1 - $promoDiscountPct / 100;
$finalPrice  = round($originalPrice * $tierFactor * $promoFactor, 2);
$discountAmount = round($originalPrice - $finalPrice, 2);
```

### API shape for service endpoints

Both `GET /api/v1/services` and `GET /api/v1/doctors/{doctor}/services` add `promo_discount_pct`:

```json
{
  "id": 1,
  "name": "Teeth Cleaning",
  "price": "45.00",
  "loyalty_discount_pct": 5.0,
  "promo_discount_pct": 2.5
}
```

- `loyalty_discount_pct` — unchanged: tier discount for this patient, `null` for non-members/guests
- `promo_discount_pct` — active rule's `discount_pct` for this service, `null` if none (regardless of auth)

**Why `promo_discount_pct` is shown to all:** The promo is a publicly advertised promotion on the service. Even guests can see it — they just won't benefit (pricing still requires membership). This keeps the display logic simple.

### Appointment `discount_pct` column

The `appointments.discount_pct` column stores the total effective discount at booking time. It should store the combined effective discount (not tier or promo separately). Formula:

```
combined_pct = (1 − (1 − tier/100) × (1 − promo/100)) × 100
```

This is a backwards-compatible change — existing appointments keep their stored values.

### Filament unique service validation

The `LoyaltyRuleForm` should add `Rule::unique('loyalty_rules', 'service_id')->ignore($record)` to `service_id` so the form rejects duplicates before hitting the DB constraint.

## Risks / Trade-offs

- **[Risk] Existing `discount_pct` data in production** — If any rules were seeded with non-zero `discount_pct` but `is_active = false`, they could unexpectedly start applying discounts when migrated (new default is `true`). → Mitigation: migration sets `is_active = true` for all existing rows; admin should review after deploy. The demo seeder sets `discount_pct = 0` for all rules, so no impact there.
- **[Risk] `valid_months` evaluated at query time** — The `scopeActive()` scope uses a SQL date comparison, which is correct but means rules don't "expire" with a triggered event — they just stop being included in queries. → This is acceptable; no background job needed.
- **[Risk] `UNIQUE(service_id)` migration failure** — If production data already has duplicate `service_id` rows, the migration will fail. → Mitigation: the migration should check or clean duplicates first (keep highest `id` per service).
