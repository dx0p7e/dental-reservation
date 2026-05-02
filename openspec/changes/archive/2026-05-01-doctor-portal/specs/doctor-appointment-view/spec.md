## ADDED Requirements

### Requirement: Doctor can view their upcoming appointments
`DoctorDashboardController@index` SHALL load the authenticated doctor's own upcoming appointments (status `pending` or `confirmed`, date on or after today, ordered by slot date/time ascending) and pass them as Inertia props to the `doctor/Dashboard` component. "Upcoming" is resolved server-side — no client-initiated fetch is required.

#### Scenario: Doctor visits the dashboard
- **WHEN** an authenticated doctor visits `GET /doctor/dashboard`
- **THEN** the `doctor/Dashboard` Inertia component is rendered with an `appointments` prop containing the doctor's upcoming appointments, each including patient name, service name, slot date, slot start time, and status

#### Scenario: Non-doctor is rejected from the dashboard
- **WHEN** an authenticated non-doctor visits `GET /doctor/dashboard`
- **THEN** the response is 403

#### Scenario: Unauthenticated user is redirected
- **WHEN** an unauthenticated user visits `GET /doctor/dashboard`
- **THEN** they are redirected to the login page
