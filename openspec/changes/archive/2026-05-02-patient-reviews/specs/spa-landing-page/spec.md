## MODIFIED Requirements

### Requirement: Landing page renders a testimonials section
The system SHALL render a reviews/testimonials section (`bg-clinic-surface`) on `LandingView.vue`. Review data SHALL be fetched from `GET /api/v1/reviews` on mount. The section SHALL display the 3 most recent reviews. Each review card SHALL display: star rating in `text-clinic-teal`, the review `body` text (truncated with `line-clamp-3`), and the `patient_name` (first name + last initial). While the API call is in-flight, 3 animated skeleton placeholder cards SHALL be shown. If the API returns no reviews, the section SHALL be hidden. A "Peržiūrėti visus atsiliepimus →" `RouterLink` to `/reviews` SHALL appear below the cards.

#### Scenario: Three most recent reviews are displayed
- **WHEN** a visitor views the landing page and `GET /api/v1/reviews` returns reviews
- **THEN** the three most recent review cards are visible with star rating, truncated body, and patient name

#### Scenario: Section hidden when no reviews
- **WHEN** `GET /api/v1/reviews` returns an empty array
- **THEN** the reviews section is not rendered on the landing page

#### Scenario: Skeleton shown during loading
- **WHEN** the landing page is loading and the reviews API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible in the reviews section

#### Scenario: View all link visible
- **WHEN** at least one review is displayed
- **THEN** a "Peržiūrėti visus atsiliepimus →" link pointing to `/reviews` is visible below the cards
