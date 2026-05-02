## ADDED Requirements

### Requirement: patient_reviews table stores one review per patient
The system SHALL have a `patient_reviews` table with columns: `id` (bigint PK), `patient_id` (FK → `users.id`, nullable on delete SET NULL), `rating` (tinyint 1–5, not null), `title` (varchar 150, nullable), `body` (text, not null, max 1000 chars), `is_published` (boolean, default true), `created_at`, `updated_at`. A unique constraint SHALL be enforced on `patient_id`. The `PatientReview` Eloquent model SHALL have `rating`, `title`, `body`, `is_published` in `$fillable` and belong to `User` via `patient_id`.

#### Scenario: Migration creates the table
- **WHEN** `php artisan migrate` is run
- **THEN** the `patient_reviews` table exists with all specified columns and the unique constraint on `patient_id`

#### Scenario: Duplicate review prevented at DB level
- **WHEN** a second review is inserted for the same `patient_id`
- **THEN** a database unique constraint violation is raised

### Requirement: GET /api/v1/reviews returns published reviews
The system SHALL expose a public `GET /api/v1/reviews` endpoint (no auth required) that returns up to 50 most recent published reviews ordered by `created_at DESC`. The response SHALL use the `ReviewResource` which exposes: `id`, `rating`, `title`, `body`, `patient_name` (first name + last initial of the linked user, e.g. "Jonas S."), `created_at`. The response SHALL be wrapped in a `data` array.

#### Scenario: Returns reviews for any visitor
- **WHEN** an unauthenticated visitor calls `GET /api/v1/reviews`
- **THEN** the response is HTTP 200 with a `data` array of review objects

#### Scenario: patient_name is anonymised
- **WHEN** a review is returned by the API
- **THEN** the `patient_name` field contains only the first name and last initial (e.g., "Eglė M."), not the full name or email

#### Scenario: Only published reviews returned
- **WHEN** a review with `is_published = false` exists
- **THEN** it is not included in the `GET /api/v1/reviews` response

#### Scenario: Returns at most 50 reviews
- **WHEN** more than 50 published reviews exist
- **THEN** only the 50 most recent are returned

### Requirement: POST /api/v1/reviews stores a new patient review
The system SHALL expose an authenticated `POST /api/v1/reviews` endpoint. The request SHALL require `rating` (integer 1–5) and `body` (string, 10–1000 chars). `title` (string, max 150 chars) is optional. If the authenticated patient has already submitted a review, the system SHALL return HTTP 409 with a JSON error. On success, the system SHALL return HTTP 201 with the created `ReviewResource`.

#### Scenario: Authenticated patient submits a review
- **WHEN** an authenticated patient with no existing review calls `POST /api/v1/reviews` with valid `rating` and `body`
- **THEN** a new review is created and HTTP 201 is returned with the review data

#### Scenario: Duplicate submission rejected
- **WHEN** an authenticated patient who already has a review calls `POST /api/v1/reviews`
- **THEN** the system returns HTTP 409 with `{"message": "Jūs jau palikote atsiliepimą."}`

#### Scenario: Invalid rating rejected
- **WHEN** `rating` is outside 1–5 or missing
- **THEN** the system returns HTTP 422 with validation errors

#### Scenario: Body too long rejected
- **WHEN** `body` exceeds 1000 characters
- **THEN** the system returns HTTP 422 with validation errors

#### Scenario: Unauthenticated request rejected
- **WHEN** an unauthenticated request is made to `POST /api/v1/reviews`
- **THEN** the system returns HTTP 401

### Requirement: ReviewSeeder seeds realistic Lithuanian reviews
The system SHALL have a `ReviewSeeder` class that creates at least 6 realistic Lithuanian-language reviews linked to the seeded patient users. The seeder SHALL be called from `DemoSeeder`. Reviews SHALL have varied ratings (mix of 4 and 5 stars), meaningful Lithuanian title and body text, and `is_published = true`.

#### Scenario: Seeder creates Lithuanian reviews
- **WHEN** `php artisan db:seed --class=ReviewSeeder` is run after patient data exists
- **THEN** at least 6 `patient_reviews` rows exist with Lithuanian text content

### Requirement: /reviews SPA page displays all reviews in a grid
The system SHALL render a `/reviews` public route in the SPA. The page SHALL:
- Display a page heading (e.g. "Atsiliepimai").
- Fetch all reviews from `GET /api/v1/reviews` on mount.
- Render reviews in a 3-column responsive grid (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`) using a `ReviewCard` component.
- Show a skeleton loading state (3 pulsing placeholder cards) while the API call is in-flight.
- If the authenticated user has not yet submitted a review, show an "Palikti atsiliepimą" button at the top-right that opens a `ReviewForm` modal.
- If the user is not authenticated, show a "Prisijunkite, norėdami palikti atsiliepimą" note instead of the button.
- After successful form submission, the new review SHALL appear in the grid without a full page reload.

#### Scenario: Visitor sees all reviews
- **WHEN** an unauthenticated visitor navigates to `/reviews`
- **THEN** all published reviews are displayed in a grid with rating stars, patient name, and body text

#### Scenario: Authenticated patient sees Add review button
- **WHEN** an authenticated patient who has not yet submitted a review visits `/reviews`
- **THEN** an "Palikti atsiliepimą" button is visible at the top of the page

#### Scenario: Already-reviewed patient sees no Add button
- **WHEN** an authenticated patient who has already submitted a review visits `/reviews`
- **THEN** the "Palikti atsiliepimą" button is not visible

#### Scenario: Skeleton shown during loading
- **WHEN** the page is loading and the API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible

### Requirement: ReviewCard component renders a single review
The system SHALL have a `ReviewCard.vue` component that accepts a `review` prop with the shape from `ReviewResource`. The card SHALL display: star rating (filled stars in `text-clinic-teal`), `title` if present, `body` text, and `patient_name` with `created_at` formatted as a short date.

#### Scenario: Card renders all review fields
- **WHEN** `ReviewCard` is passed a review with rating 5, title, body, and patient_name
- **THEN** 5 filled stars, the title, body, patient name, and formatted date are all visible

### Requirement: ReviewForm modal allows submitting a review
The system SHALL have a `ReviewForm.vue` component that renders as a modal/overlay. It SHALL contain: a star-picker (1–5, interactive), an optional title field (max 150 chars), a required body textarea (min 10, max 1000 chars), a submit button, and a cancel button. On successful submission, the modal SHALL close and emit a `submitted` event with the new review object. Validation errors from the API SHALL be displayed inline.

#### Scenario: Patient submits a valid review
- **WHEN** the patient fills in rating (≥1) and body (≥10 chars) and clicks submit
- **THEN** `POST /api/v1/reviews` is called, the modal closes on success, and the new review appears in the grid

#### Scenario: Validation errors shown inline
- **WHEN** the patient submits with an empty body
- **THEN** an inline validation message is displayed without closing the modal
