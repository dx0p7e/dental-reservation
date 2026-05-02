## ADDED Requirements

### Requirement: GET /api/v1/services includes loyalty_discount_pct for authenticated patients
The system SHALL include a `loyalty_discount_pct` field on each item returned by `GET /api/v1/services`. For an authenticated patient request (valid Bearer token), the value SHALL equal `LoyaltyTier.discount_bonus_pct` for the patient's current tier as stored in their `LoyaltyAccount`. If the patient has no `LoyaltyAccount`, the value SHALL be `null`. For unauthenticated requests, the value SHALL be `null`.

#### Scenario: Authenticated patient with Silver tier receives 5.0 discount
- **WHEN** an authenticated patient with a Silver-tier `LoyaltyAccount` calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: 5.0`

#### Scenario: Authenticated patient with Gold tier receives 10.0 discount
- **WHEN** an authenticated patient with a Gold-tier `LoyaltyAccount` calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: 10.0`

#### Scenario: Authenticated patient with Standard tier receives 0.0 discount
- **WHEN** an authenticated patient with a Standard-tier `LoyaltyAccount` calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: 0.0`

#### Scenario: Authenticated patient with no LoyaltyAccount receives null
- **WHEN** an authenticated patient with no `LoyaltyAccount` record calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: null`

#### Scenario: Unauthenticated request receives null
- **WHEN** an unauthenticated request calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: null`
- **AND** the response status is 200 (endpoint remains public)
