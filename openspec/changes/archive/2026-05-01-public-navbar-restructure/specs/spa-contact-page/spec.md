## ADDED Requirements

### Requirement: /contact renders a static contact information and form page
The system SHALL render a new `ContactView.vue` at route `/contact` (no auth required) with:
1. **Static contact info** — clinic address, phone number, and email (sourced from hardcoded constants in the component, to be updated for the clinic)
2. **Contact form** — fields: name (required), email (required, email format), subject (required), message (required, textarea); a "Siųsti" submit button

On form submit, the component SHALL POST to `POST /api/v1/contact` with the four fields. On success, the form SHALL be replaced with a success message: "Jūsų žinutė išsiųsta. Susisieksime netrukus." On server-side validation failure, inline errors SHALL be displayed next to each invalid field.

#### Scenario: Guest views the contact page without auth
- **WHEN** an unauthenticated visitor navigates to `/contact`
- **THEN** the page renders the static contact info and the form without redirecting to login

#### Scenario: Successful form submission shows success state
- **WHEN** a visitor submits the form with all required fields filled correctly
- **THEN** a POST is sent to `/api/v1/contact` and on success the form is replaced by the success message

#### Scenario: Validation errors are shown inline
- **WHEN** the server returns a 422 validation error
- **THEN** each field with an error shows the error message below it

### Requirement: POST /api/v1/contact endpoint accepts and forwards contact form submissions
The system SHALL expose a `POST /api/v1/contact` route with no authentication requirement and a `throttle:5,1` rate limit (5 requests per minute per IP).

The endpoint SHALL:
- Validate: `name` (required, string, max 255), `email` (required, email, max 255), `subject` (required, string, max 255), `message` (required, string, max 5000)
- On valid input: send a `ContactFormMail` mailable to `config('app.contact_email', config('mail.from.address'))` and return `{ "message": "Sent" }` with HTTP 200
- On mail failure: return HTTP 500 with `{ "message": "Failed to send. Please try again later." }`

#### Scenario: Valid form data triggers email
- **WHEN** a POST request with valid name, email, subject, and message is sent to `/api/v1/contact`
- **THEN** a `ContactFormMail` is sent to the clinic contact address and the response is HTTP 200 `{ "message": "Sent" }`

#### Scenario: Missing required fields returns 422
- **WHEN** a POST request is sent with one or more missing required fields
- **THEN** the endpoint returns HTTP 422 with validation error details

#### Scenario: Rate limiting blocks excessive requests
- **WHEN** more than 5 requests are sent from the same IP within one minute
- **THEN** the endpoint returns HTTP 429 Too Many Requests
