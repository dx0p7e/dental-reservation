## MODIFIED Requirements

### Requirement: Landing page renders a services section with a three-column card grid
The system SHALL render a services section (`bg-white`) with a "Our Services" heading and a three-column card grid. The service data SHALL be fetched from `GET /api/v1/services` on mount; there is no hardcoded array. Each card SHALL have `border border-clinic-border rounded-lg p-6` and contain: service name in `font-semibold`, duration and price in `text-clinic-muted` (formatted as `{duration_minutes} min · €{price}`), and a "Book →" `RouterLink` in `text-clinic-blue`. Cards SHALL NOT display a Heroicon or any icon (no icon field is present in the API response). If the API returns more than 6 services, only the first 6 SHALL be rendered and a "View all services →" `RouterLink` to `/services` SHALL appear below the grid. If the API call fails, the section SHALL degrade silently to an empty services array (no error banner). While the API call is in-flight, 3 animated skeleton placeholder cards (`animate-pulse`) SHALL be shown.

#### Scenario: Services section shows live API cards
- **WHEN** a visitor views the landing page and `GET /api/v1/services` returns services
- **THEN** the services section displays one card per service (up to 6), each with name, duration, price, and "Book →" link — no icon

#### Scenario: View all link appears when more than 6 services exist
- **WHEN** `GET /api/v1/services` returns more than 6 services
- **THEN** only the first 6 cards are rendered and a "View all services →" link pointing to `/services` is visible below the grid

#### Scenario: Silent empty state on API failure
- **WHEN** `GET /api/v1/services` fails
- **THEN** the services section renders with no cards and no error message

#### Scenario: Skeleton cards shown during loading
- **WHEN** the landing page is loading and the services API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible in the services grid area
