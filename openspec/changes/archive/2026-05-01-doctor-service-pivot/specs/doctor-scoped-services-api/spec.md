## ADDED Requirements

### Requirement: Scoped services endpoint returns only services a doctor provides
The system SHALL expose `GET /api/v1/doctors/{doctor}/services` returning only the services linked to that doctor via the `doctor_service` pivot. The endpoint SHALL be publicly accessible (no authentication required). The response SHALL be a JSON array of service objects: `[{ id, name, price, loyalty_discount_pct }]`.

#### Scenario: Doctor with assigned services
- **WHEN** a client sends `GET /api/v1/doctors/1/services` and doctor 1 has two services assigned
- **THEN** the response is a JSON array containing exactly those two services

#### Scenario: Doctor with no services assigned
- **WHEN** a client sends `GET /api/v1/doctors/1/services` and doctor 1 has no services assigned
- **THEN** the response is an empty JSON array `[]`

#### Scenario: Non-existent doctor
- **WHEN** a client sends `GET /api/v1/doctors/9999/services` and no doctor with that ID exists
- **THEN** the system returns HTTP 404
