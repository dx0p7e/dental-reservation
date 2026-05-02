## ADDED Requirements

### Requirement: Service selection step shows loyalty discount hint when applicable
The booking flow's service selection step (`DoctorSlotsView`) SHALL display a discount indicator alongside each service option when the authenticated patient's `loyalty_discount_pct` is greater than 0. The indicator SHALL include the discount percentage value.

#### Scenario: Silver-tier patient sees discount hint on service options
- **WHEN** a Silver-tier patient views the service `<select>` in the booking flow
- **THEN** each service option includes a discount hint such as "5% member discount"

#### Scenario: Standard-tier patient sees no discount hint
- **WHEN** a Standard-tier patient (0% discount) views the service `<select>`
- **THEN** service options are displayed without a discount indicator

#### Scenario: Guest patient sees no discount hint
- **WHEN** an unauthenticated user views the service `<select>`
- **THEN** service options are displayed without a discount indicator

### Requirement: Confirmation step shows discounted price when loyalty discount applies
The booking confirmation step (`BookView`) SHALL display the discounted effective price when the selected service has `loyalty_discount_pct > 0`. The original price SHALL remain visible for reference. The discounted price SHALL be computed as `price × (1 − discount_pct / 100)`, rounded to 2 decimal places.

#### Scenario: Silver-tier patient sees discounted price at confirmation
- **WHEN** a Silver-tier patient (5% discount) reaches the confirmation step with a service priced at $100
- **THEN** the confirmation summary shows both the original price ($100) and the discounted price ($95.00)

#### Scenario: Standard-tier patient sees only the base price at confirmation
- **WHEN** a Standard-tier patient (0% discount) reaches the confirmation step
- **THEN** the confirmation summary shows only the base price with no discount row
