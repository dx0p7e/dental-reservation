## MODIFIED Requirements

### Requirement: Main navbar shows public links regardless of auth state
The system SHALL render a navigation bar on every SPA page with the following links always visible (authenticated or not):
- **Home** → `/`
- **Doctors** → `/doctors`
- **Services** (Paslaugos) → `/services`
- **Loyalty** → `/loyalty`
- **About Us** → `/about`
- **Contact Us** → `/contact`
- **Book** → navigates to `/dashboard/book` if authenticated, `/login` if guest

#### Scenario: Guest sees all public nav links including Services
- **WHEN** an unauthenticated visitor views any page
- **THEN** all six public nav links (Home, Doctors, Services, Loyalty, About Us, Contact Us) and the Book link are visible in the navbar

#### Scenario: Authenticated user sees Services nav link
- **WHEN** an authenticated user views any page
- **THEN** the "Paslaugos" (Services) link pointing to `/services` is visible in the navbar

#### Scenario: Book link redirects guest to login
- **WHEN** an unauthenticated visitor clicks the "Book" nav link
- **THEN** the visitor is navigated to `/login`

#### Scenario: Book link navigates authenticated user to dashboard
- **WHEN** an authenticated user clicks the "Book" nav link
- **THEN** the user is navigated to `/dashboard/book`
