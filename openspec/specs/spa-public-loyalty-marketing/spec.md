## ADDED Requirements

### Requirement: /loyalty renders a public marketing page explaining the loyalty programme
The system SHALL render a new `LoyaltyMarketingView.vue` at route `/loyalty` (no auth required). The existing `LoyaltyView.vue` (authenticated dashboard) SHALL move to `/dashboard/loyalty` and be renamed `LoyaltyDashboardView.vue`.

The marketing page SHALL contain four sections:
1. **Hero** — heading "Kaupdami taškus — gaukite nuolaidų" with a short introductory paragraph
2. **How it works** — three-step visual: Book → Complete Appointment → Earn Points
3. **Tier cards** — Standard / Silver / Gold displayed as three visually distinct cards showing point threshold and discount percentage (hardcoded: Standard 0 pts / 0%, Silver 500 pts / 10%, Gold 1500 pts / 20%)
4. **CTA** — "Prisijunkite ir pradėkite kaupti" button linking to `/register` when not authenticated, or `/dashboard/loyalty` when authenticated

#### Scenario: Guest views the loyalty marketing page
- **WHEN** an unauthenticated visitor navigates to `/loyalty`
- **THEN** the page renders with the hero, how-it-works, tier cards, and CTA sections without redirecting to login

#### Scenario: CTA links to /register for guests
- **WHEN** an unauthenticated visitor views the loyalty marketing page
- **THEN** the CTA button links to `/register`

#### Scenario: CTA links to /dashboard/loyalty for authenticated users
- **WHEN** an authenticated user views the loyalty marketing page
- **THEN** the CTA button links to `/dashboard/loyalty`

#### Scenario: Tier cards display correct thresholds and discounts
- **WHEN** a visitor views the loyalty marketing page
- **THEN** three tier cards are shown with Standard (0 pts, 0%), Silver (500 pts, 10%), and Gold (1500 pts, 20%)
