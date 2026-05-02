## ADDED Requirements

### Requirement: GET /api/v1/doctors includes services per doctor
The system SHALL include a `services` array in the `DoctorResource` API response. Each entry SHALL contain `{ id, name, price, loyalty_discount_pct }`. The controller SHALL eager-load the `services` relationship to avoid N+1 queries.

#### Scenario: Doctor with assigned services
- **WHEN** a client sends `GET /api/v1/doctors` and doctor 1 has 2 services assigned
- **THEN** the doctor 1 object in the response contains a `services` array with 2 entries

#### Scenario: Doctor with no services assigned
- **WHEN** a client sends `GET /api/v1/doctors` and a doctor has no services
- **THEN** the `services` key in that doctor's response is an empty array `[]`
