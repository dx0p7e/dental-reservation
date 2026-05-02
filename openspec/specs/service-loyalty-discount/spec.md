## MODIFIED Requirements

### Requirement: GET /api/v1/services includes loyalty_discount_pct for authenticated patients
The system SHALL include a `loyalty_discount_pct` field on each item returned by `GET /api/v1/services`. For an authenticated patient request (valid Bearer token), the value SHALL equal `LoyaltyTier.discount_bonus_pct` for the patient's current tier as stored in their `LoyaltyAccount`. If the patient has no `LoyaltyAccount`, the value SHALL be `null`. For unauthenticated requests, the value SHALL be `null`.

The system SHALL also include a `promo_discount_pct` field on each item. The value SHALL equal the `discount_pct` of the active `LoyaltyRule` for that service (where `is_active = true` AND the rule has not expired via `valid_months`). If no active rule exists for the service, the value SHALL be `null`. This field is returned for all requests regardless of authentication — it is publicly visible promotional information.

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

#### Scenario: Unauthenticated request receives null for loyalty_discount_pct
- **WHEN** an unauthenticated request calls `GET /api/v1/services`
- **THEN** each service in the response includes `loyalty_discount_pct: null`
- **AND** the response status is 200 (endpoint remains public)

#### Scenario: Service with active promo rule includes promo_discount_pct
- **WHEN** service 1 has an active `LoyaltyRule` with `discount_pct = 2.5`
- **THEN** the response for service 1 includes `promo_discount_pct: 2.5` for all callers (authenticated or not)

#### Scenario: Service with no active promo rule returns null
- **WHEN** service 1 has no active `LoyaltyRule`
- **THEN** the response for service 1 includes `promo_discount_pct: null`

#### Scenario: Expired promo rule not included
- **WHEN** service 1 has a `LoyaltyRule` with `is_active = true` but `created_at + valid_months < now()`
- **THEN** the response for service 1 includes `promo_discount_pct: null`
