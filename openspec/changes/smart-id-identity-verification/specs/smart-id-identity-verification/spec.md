## ADDED Requirements

### Requirement: Patient can initiate Smart-ID identity verification from their profile
An authenticated patient with a non-null `phone_verified_at` (or at any time) SHALL be able to initiate Smart-ID verification from the profile page by providing their personal identification number and country. The system SHALL call the Smart-ID API (notification-based flow), store the session state in cache, and return a 4-digit verification code and a polling token to the frontend. The personal identification number SHALL NOT be persisted after the API call completes.

#### Scenario: Successful initiation for Lithuanian user
- **WHEN** a patient posts `POST /api/v1/smart-id/initiate` with `{ personal_code: "40504040001", country: "LT" }`
- **THEN** the system returns HTTP 200 with `{ verification_code: "1234", polling_token: "<uuid>" }`

#### Scenario: Validation rejects missing personal code
- **WHEN** a patient posts `POST /api/v1/smart-id/initiate` with no `personal_code`
- **THEN** the system returns HTTP 422 with a validation error for `personal_code`

#### Scenario: Validation rejects unsupported country
- **WHEN** a patient posts `POST /api/v1/smart-id/initiate` with `country: "US"`
- **THEN** the system returns HTTP 422 with a validation error for `country`

#### Scenario: Rate limit prevents abuse
- **WHEN** a patient initiates verification more than 3 times within 5 minutes
- **THEN** the system returns HTTP 429 Too Many Requests

#### Scenario: Already-verified patient can initiate again
- **WHEN** a patient whose `smart_id_verified_at` is already set initiates verification
- **THEN** the system processes the request normally (re-verification is allowed)

### Requirement: Patient can poll for Smart-ID session result
After initiating, the frontend SHALL poll `GET /api/v1/smart-id/poll/{token}` to retrieve the session outcome. The system SHALL return a status of `running`, `ok`, or `failed`. On `ok`, the system SHALL set `smart_id_verified_at = now()` on the user record. The polling token SHALL be scoped to the authenticated user — another user's token SHALL return 403.

#### Scenario: Session still in progress
- **WHEN** the patient polls and the Smart-ID session has not completed yet
- **THEN** the system returns HTTP 200 with `{ status: "running" }`

#### Scenario: User approves in Smart-ID app
- **WHEN** the patient polls and the Smart-ID session completed with result OK
- **THEN** the system sets `smart_id_verified_at` on the user and returns HTTP 200 with `{ status: "ok" }`

#### Scenario: User refuses in Smart-ID app
- **WHEN** the patient polls and the user refused the session
- **THEN** the system returns HTTP 200 with `{ status: "failed", reason: "refused" }`

#### Scenario: Session timed out
- **WHEN** the patient polls and the Smart-ID session timed out
- **THEN** the system returns HTTP 200 with `{ status: "failed", reason: "timeout" }`

#### Scenario: Expired or unknown polling token
- **WHEN** the patient polls with a token that is not in cache (expired or never existed)
- **THEN** the system returns HTTP 404

#### Scenario: Token belongs to a different user
- **WHEN** a patient polls with a valid token that was created by a different user
- **THEN** the system returns HTTP 403

### Requirement: Smart-ID verification status is visible in the patient profile UI
The patient profile page SHALL display a "Tapatybės patvirtinimas (Smart-ID)" section. If `smart_id_verified_at` is set, it SHALL show a verified badge with the date. If not set, it SHALL show a form to initiate verification. After successful polling, the section SHALL update to the verified state without a full page reload.

#### Scenario: Profile shows verified state
- **WHEN** the profile page loads and the user's `smart_id_verified_at` is non-null
- **THEN** the section shows a success badge and the verification date, and the form is hidden

#### Scenario: Profile shows unverified form
- **WHEN** the profile page loads and the user's `smart_id_verified_at` is null
- **THEN** the section shows the personal code input, country selector, and submit button

#### Scenario: Verification code is displayed prominently after initiation
- **WHEN** the patient submits the form and the backend returns a verification code
- **THEN** the section transitions to a "pending approval" state showing the 4-digit code and a spinner

#### Scenario: Section updates after successful poll
- **WHEN** a poll response returns `{ status: "ok" }`
- **THEN** the section transitions to the verified state without a page reload

#### Scenario: Error message shown on failure
- **WHEN** a poll response returns `{ status: "failed" }`
- **THEN** the section shows a localised error message and a retry button

### Requirement: Smart-ID verification status is visible to admins in Filament
The Filament admin user table and user edit form SHALL display `smart_id_verified_at`. In the table it SHALL appear as a column with a badge (verified / unverified). In the edit form it SHALL be a read-only field.

#### Scenario: Admin sees verified badge in user table
- **WHEN** an admin views the Users resource table and a user has `smart_id_verified_at` set
- **THEN** the column shows a green "Patvirtinta" badge with the date

#### Scenario: Admin sees unverified state in user table
- **WHEN** an admin views the Users resource table and a user has `smart_id_verified_at` null
- **THEN** the column shows a gray "Nepatvirtinta" badge

#### Scenario: Admin cannot edit smart_id_verified_at
- **WHEN** an admin opens a user edit form
- **THEN** the `smart_id_verified_at` field is read-only (no input to modify it)
