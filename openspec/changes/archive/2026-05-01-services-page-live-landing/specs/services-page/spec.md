## ADDED Requirements

### Requirement: ServicesView renders a hero header and a live services grid
The system SHALL render `ServicesView.vue` at route `/services` (no `requiresAuth` guard) with:
- A full-width hero header (`bg-teal-50`) containing a page title "Our Services", a one-line subtitle "Professional medical services at Druskininkai Clinic", and a short introductory paragraph
- A services grid (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`) below the hero, populated by fetching `GET /api/v1/services` on mount
- Each service card (`border rounded-xl p-6 shadow-sm bg-white`) SHALL display: service name as `<h2>`, a duration pill badge (`rounded-full bg-teal-100 text-teal-800 text-xs px-3 py-1`) showing `{duration_minutes} min`, price prominently (`text-2xl font-semibold text-clinic-blue`), the full `description` field as `<p class="text-clinic-muted text-sm leading-relaxed mt-3">`, and a "Book now →" `<RouterLink>` to `/doctors`
- The `Service` TypeScript interface from `@spa/types` SHALL be used to type the services array

#### Scenario: Services grid renders live cards
- **WHEN** a visitor navigates to `/services` and the API returns at least one service
- **THEN** the page displays one card per service with name, duration badge, price, description, and "Book now →" link

#### Scenario: Book now link routes to general booking entry
- **WHEN** a visitor clicks "Book now →" on any services card
- **THEN** the router navigates to `/doctors`

### Requirement: ServicesView shows a loading skeleton while fetching
The system SHALL display exactly 3 animated skeleton placeholder cards (`animate-pulse bg-gray-200 rounded-xl`) while `GET /api/v1/services` is in-flight.

#### Scenario: Skeleton cards appear before API resolves
- **WHEN** the visitor loads `/services` and the API has not yet responded
- **THEN** 3 pulsing skeleton cards are visible in place of the services grid

### Requirement: ServicesView shows an empty state when no services are available
If `GET /api/v1/services` returns an empty array, the system SHALL display a centred message: "No services are currently listed. Please contact the clinic directly."

#### Scenario: Empty state shown when API returns zero services
- **WHEN** `GET /api/v1/services` responds with an empty array
- **THEN** the grid is replaced by the centred empty-state message

### Requirement: ServicesView shows an error state with a Retry button on API failure
If `GET /api/v1/services` fails (network error or 5xx), the system SHALL display a teal-bordered inline alert: "Could not load services. Please try again later." with a "Retry" button that re-triggers the fetch.

#### Scenario: Error alert shown on fetch failure
- **WHEN** `GET /api/v1/services` returns an error
- **THEN** the teal-bordered alert and "Retry" button are displayed instead of the grid

#### Scenario: Retry button re-fetches services
- **WHEN** the visitor clicks the "Retry" button after a fetch failure
- **THEN** the fetch is triggered again and the loading skeleton is shown
