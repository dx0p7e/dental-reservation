## ADDED Requirements

### Requirement: Discount and final price are stored at slot-based booking time

When a patient books a slot-based appointment, the system SHALL resolve the patient's loyalty tier discount rate and store `discount_pct` (the percentage applied) and `final_price` (the computed discounted price) on the appointment record.

#### Scenario: Patient with Silver tier books an appointment

- **GIVEN** a patient has a Silver loyalty account with a non-zero `discount_bonus_pct`
- **WHEN** the patient submits `POST /api/v1/appointments`
- **THEN** the created appointment's `discount_pct` equals the Silver tier's `discount_bonus_pct`
- **AND** `final_price` equals `service.price * (1 - discount_pct / 100)` rounded to 2 decimal places

#### Scenario: Patient with Standard tier (0% discount) books an appointment

- **GIVEN** a patient has a Standard loyalty account with `discount_bonus_pct = 0`
- **WHEN** the patient submits `POST /api/v1/appointments`
- **THEN** `discount_pct` is 0.00 and `final_price` equals `service.price`

#### Scenario: API response includes discount fields

- **WHEN** the appointment resource is returned by any appointment endpoint
- **THEN** the JSON includes `discount_pct` and `final_price` (which may be null for request-based appointments)

### Requirement: Request-based appointments store deferred pricing

When a patient submits a request-based booking (`POST /api/v1/appointments/request`), the system SHALL store `discount_pct = 0` and `final_price = null`, deferring price calculation to admin confirmation.

#### Scenario: Request-based booking has null final_price

- **WHEN** a patient submits a request-based appointment
- **THEN** the created appointment has `discount_pct = 0` and `final_price = null`
