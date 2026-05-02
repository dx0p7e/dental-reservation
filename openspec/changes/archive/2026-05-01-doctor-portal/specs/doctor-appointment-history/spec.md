## ADDED Requirements

### Requirement: Doctor can view their appointment history
`DoctorHistoryController@index` SHALL load the authenticated doctor's own past appointments (status `completed`, `no_show`, or `cancelled`, ordered by slot date descending, paginated) and pass them as Inertia props to the `doctor/History` component. Pagination is resolved server-side — no client-initiated fetch is required.

#### Scenario: Doctor visits the history page
- **WHEN** an authenticated doctor visits `GET /doctor/history`
- **THEN** the `doctor/History` Inertia component is rendered with a paginated `appointments` prop containing the doctor's past appointments, each including patient name, service name, date, and final status

#### Scenario: Non-doctor is rejected from the history page
- **WHEN** an authenticated non-doctor visits `GET /doctor/history`
- **THEN** the response is 403

#### Scenario: Unauthenticated user is redirected
- **WHEN** an unauthenticated user visits `GET /doctor/history`
- **THEN** they are redirected to the login page
