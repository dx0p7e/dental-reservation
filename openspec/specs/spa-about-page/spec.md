## ADDED Requirements

### Requirement: /about renders a static clinic information page
The system SHALL render a new `AboutView.vue` at route `/about` (no auth required) with the following sections:

1. **Clinic info** — clinic name, founding year, and mission statement (placeholder Lithuanian text that can be replaced by the clinic)
2. **"Mūsų komanda"** — a simplified listing of active doctors using the same data source as `DoctorsView.vue` (fetched from `GET /api/v1/doctors`) or a static fallback if the API call fails; each doctor shown with name and specialization
3. **Technology note** — "Sistema sukurta siekiant patogaus pacientų aptarnavimo ir lojalumo skatinimo" displayed as an informational paragraph

#### Scenario: Guest views the About page without auth
- **WHEN** an unauthenticated visitor navigates to `/about`
- **THEN** the page renders the clinic info, team section, and technology note without redirecting to login

#### Scenario: Team section shows active doctors
- **WHEN** the `/api/v1/doctors` endpoint returns a list of doctors
- **THEN** each active doctor's name and specialization are displayed in the team section

#### Scenario: Team section shows fallback when API fails
- **WHEN** the doctors API call fails
- **THEN** the team section renders an empty state or static placeholder message instead of crashing
