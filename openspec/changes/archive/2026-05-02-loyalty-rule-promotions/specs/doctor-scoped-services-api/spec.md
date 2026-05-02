## MODIFIED Requirements

### Requirement: Scoped services endpoint returns only services a doctor provides
The system SHALL expose `GET /api/v1/doctors/{doctor}/services` returning only the services linked to that doctor via the `doctor_service` pivot. The endpoint SHALL be publicly accessible (no authentication required). The response SHALL be a JSON array of service objects: `[{ id, name, price, loyalty_discount_pct, promo_discount_pct }]`.

`promo_discount_pct` SHALL equal the `discount_pct` of the active `LoyaltyRule` for that service (where `is_active = true` and not expired), or `null` if no active rule exists. It is returned for all callers regardless of authentication.

#### Scenario: Doctor with assigned services
- **WHEN** a client sends `GET /api/v1/doctors/1/services` and doctor 1 has two services assigned
- **THEN** the response is a JSON array containing exactly those two services, each including `promo_discount_pct`

#### Scenario: Service with active promo includes promo_discount_pct
- **WHEN** one of doctor 1's services has an active `LoyaltyRule` with `discount_pct = 3.0`
- **THEN** that service object in the response includes `promo_discount_pct: 3.0`

#### Scenario: Doctor with no services assigned
- **WHEN** a client sends `GET /api/v1/doctors/1/services` and doctor 1 has no services assigned
- **THEN** the response is an empty JSON array `[]`

#### Scenario: Non-existent doctor
- **WHEN** a client sends `GET /api/v1/doctors/9999/services` and no doctor with that ID exists
- **THEN** the system returns HTTP 404
