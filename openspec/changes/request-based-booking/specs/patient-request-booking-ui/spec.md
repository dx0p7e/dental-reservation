## ADDED Requirements

### Requirement: Patient can navigate to a request booking form from the appointments page
The patient SPA SHALL include a "Request Appointment" link or button on the `/appointments` page that navigates to `/request-appointment`. The route SHALL require authentication.

#### Scenario: Authenticated patient navigates to the request form
- **WHEN** an authenticated patient clicks "Request Appointment" on the appointments page
- **THEN** the patient is taken to the `/request-appointment` view

#### Scenario: Unauthenticated user is redirected to login
- **WHEN** an unauthenticated user navigates directly to `/request-appointment`
- **THEN** the router redirects them to `/login`

### Requirement: Patient can submit a booking request via the SPA request form
The `/request-appointment` view SHALL provide a form with a service select (populated from `GET /api/v1/services`), a preferred date input (date picker, today or future), and an optional notes textarea. On successful submission the system SHALL call `POST /api/v1/appointments/request`, display a success message, and redirect to `/appointments`.

#### Scenario: Patient submits a valid request
- **WHEN** an authenticated patient selects a service, enters a future preferred date, and submits the form
- **THEN** the API is called, a success message is shown, and the patient is redirected to `/appointments`

#### Scenario: Patient submits with no service selected
- **WHEN** the patient submits the form without selecting a service
- **THEN** a validation error is displayed and the form is not submitted

#### Scenario: Patient submits with a past preferred date
- **WHEN** the patient enters a past date and submits
- **THEN** a client-side or server-returned validation error is shown and the form is not submitted

### Requirement: Patient appointment list displays pending requests with a "Pending Review" status indicator
The `/appointments` view SHALL display request-based appointments (those with `slot = null`) with a `"pending"` badge. The cancel button SHALL remain available while status is `pending`.

#### Scenario: Patient sees their pending request in the appointments list
- **WHEN** a patient has submitted a booking request that has not yet been confirmed
- **THEN** the appointment appears in the list with a `pending` status badge and no slot details shown
