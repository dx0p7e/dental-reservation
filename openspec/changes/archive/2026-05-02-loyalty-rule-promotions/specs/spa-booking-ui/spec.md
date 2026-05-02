## MODIFIED Requirements

### Requirement: DoctorSlotsView shows a loyalty discount badge when applicable
The system SHALL display a loyalty discount badge below the service `<select>` when the selected service has a non-zero `loyalty_discount_pct`. The badge SHALL read "{N}% member discount" with `bg-teal-100 text-clinic-teal` styling.

When the selected service also has a non-null `promo_discount_pct > 0`, a **second badge** SHALL be rendered immediately below the tier badge reading "{N}% promo discount" with `bg-orange-100 text-orange-700` styling. The two badges are independent.

#### Scenario: Loyalty badge appears for discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct > 0`
- **THEN** a teal badge appears below the service select showing the discount percentage (e.g. "5% member discount")

#### Scenario: Loyalty badge hidden for non-discounted service
- **WHEN** the patient selects a service with `loyalty_discount_pct` null or 0 AND `promo_discount_pct` null or 0
- **THEN** no badge is displayed

#### Scenario: Promo badge appears when service has an active promotion
- **WHEN** the selected service has `promo_discount_pct > 0`
- **THEN** an orange badge appears showing the promo discount percentage (e.g. "2.5% promo discount")

#### Scenario: Both badges shown simultaneously
- **WHEN** the patient has `loyalty_discount_pct > 0` and the service has `promo_discount_pct > 0`
- **THEN** both the teal member badge and the orange promo badge are visible
