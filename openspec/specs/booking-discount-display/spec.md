## MODIFIED Requirements

### Requirement: Service selection step shows loyalty discount hint when applicable
The booking flow's service selection step (`DoctorSlotsView`) SHALL display a discount indicator alongside the service `<select>` when the authenticated patient's `loyalty_discount_pct` is greater than 0. The indicator SHALL read "{N}% member discount" with `bg-teal-100 text-clinic-teal` styling.

When the selected service also has a non-null `promo_discount_pct > 0`, a **second badge** SHALL be rendered below the tier badge reading "{N}% promo discount" with `bg-orange-100 text-orange-700` styling.

Both badges are independent and may appear simultaneously, or either alone.

#### Scenario: Silver-tier patient sees tier discount badge
- **WHEN** a Silver-tier patient selects a service in the booking flow
- **THEN** a teal badge "{N}% member discount" is displayed

#### Scenario: Service with active promo shows promo badge
- **WHEN** the selected service has `promo_discount_pct > 0`
- **THEN** an orange badge "{N}% promo discount" is displayed below the tier badge

#### Scenario: Both badges shown simultaneously
- **WHEN** the patient has a tier discount and the service has an active promo
- **THEN** both the tier badge and the promo badge are visible

#### Scenario: Standard-tier patient with active promo sees only promo badge
- **WHEN** a Standard-tier patient (0% tier discount) selects a service with `promo_discount_pct = 2.5`
- **THEN** only the promo badge is displayed; no tier badge appears

#### Scenario: Standard-tier patient sees no discount hint
- **WHEN** a Standard-tier patient (0% tier discount) selects a service with no active promo
- **THEN** no discount badge is displayed

#### Scenario: Guest patient sees no tier badge but may see promo badge
- **WHEN** an unauthenticated user views the service `<select>` and the service has `promo_discount_pct = 2.5`
- **THEN** the promo badge is shown; no tier badge appears (guest has no loyalty account)

### Requirement: Confirmation step shows discounted price when loyalty discount applies
The booking confirmation step (`BookView`) SHALL display the discounted effective price when `loyalty_discount_pct > 0` or `promo_discount_pct > 0`. The original price SHALL remain visible with `line-through text-clinic-muted`. The discounted price is computed multiplicatively: `price × (1 − tier_pct/100) × (1 − promo_pct/100)`, rounded to 2 decimal places. A "You saved €X" line SHALL appear in `text-clinic-teal` showing the total monetary saving.

When both discounts apply, `BookView` SHALL show two separate discount lines before the final price:
- "{tier_pct}% member discount"
- "{promo_pct}% promo discount"

When only one discount applies, only that line is shown.

#### Scenario: Silver-tier patient with service promo sees both discount lines
- **WHEN** a Silver-tier patient (5%) books a service priced at €100 with a 2.5% promo
- **THEN** BookView shows the original price €100.00, a "5% member discount" line, a "2.5% promo discount" line, the final price €92.63, and "You saved €7.37"

#### Scenario: Silver-tier patient without promo sees only tier discount line
- **WHEN** a Silver-tier patient (5%) books a service with no active promo
- **THEN** BookView shows the original price, "5% member discount" line, final price, and savings — no promo line

#### Scenario: Standard-tier patient with promo sees only promo discount line
- **WHEN** a Standard-tier patient (0%) books a service with a 2.5% promo
- **THEN** BookView shows the original price, "2.5% promo discount" line, final price, and savings

#### Scenario: Standard-tier patient without promo sees only base price
- **WHEN** a Standard-tier patient (0%) books a service with no active promo
- **THEN** BookView shows only the base price with no discount row
