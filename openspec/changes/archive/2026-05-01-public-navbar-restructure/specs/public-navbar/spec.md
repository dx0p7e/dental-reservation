## ADDED Requirements

### Requirement: Main navbar shows public links regardless of auth state
The system SHALL render a navigation bar on every SPA page with the following links always visible (authenticated or not):
- **Home** → `/`
- **Doctors** → `/doctors`
- **Loyalty** → `/loyalty`
- **About Us** → `/about`
- **Contact Us** → `/contact`
- **Book** → navigates to `/dashboard/book` if authenticated, `/login` if guest

#### Scenario: Guest sees all public nav links
- **WHEN** an unauthenticated visitor views any page
- **THEN** all five public nav links (Home, Doctors, Loyalty, About Us, Contact Us) and the Book link are visible in the navbar

#### Scenario: Book link redirects guest to login
- **WHEN** an unauthenticated visitor clicks the "Book" nav link
- **THEN** the visitor is navigated to `/login`

#### Scenario: Book link navigates authenticated user to dashboard
- **WHEN** an authenticated user clicks the "Book" nav link
- **THEN** the user is navigated to `/dashboard/book`

### Requirement: Guest right-side shows Log In and Register buttons
The system SHALL render "Log In" and "Register" buttons in the navbar right section when no user is authenticated.

#### Scenario: Guest sees Log In and Register
- **WHEN** an unauthenticated visitor views any page
- **THEN** a "Log In" link pointing to `/login` and a "Register" button pointing to `/register` are visible on the right of the navbar

#### Scenario: Authenticated user does not see Log In / Register
- **WHEN** an authenticated user views any page
- **THEN** neither "Log In" nor "Register" is rendered in the navbar

### Requirement: Authenticated user sees a username dropdown in the navbar
The system SHALL render the authenticated user's name as a clickable trigger in the navbar right section. Clicking it opens a dropdown with personal navigation links and a logout action.

The dropdown SHALL contain:
- **Rezervuoti** → `/dashboard/book`
- **Mano vizitai** → `/dashboard/appointments`
- **Mano lojalumas** → `/dashboard/loyalty`
- **Mano profilis** → `/dashboard/profile`
- A visual divider
- **Atsijungti** → triggers logout and redirects to `/login`

The dropdown SHALL close when clicking outside of it.

#### Scenario: Authenticated user sees their name in the navbar
- **WHEN** an authenticated user views any page
- **THEN** their name is displayed as a clickable element on the right of the navbar

#### Scenario: Clicking username opens dropdown
- **WHEN** an authenticated user clicks their name
- **THEN** the dropdown opens showing all four personal links and the logout option

#### Scenario: Clicking outside closes the dropdown
- **WHEN** the dropdown is open and the user clicks outside of it
- **THEN** the dropdown closes

#### Scenario: Clicking Atsijungti logs out and redirects
- **WHEN** the user clicks "Atsijungti" in the dropdown
- **THEN** the auth session is cleared and the user is redirected to `/login`

### Requirement: Personal user pages use a /dashboard/ route prefix
The system SHALL mount all authenticated personal pages under the `/dashboard/` path prefix:
- `/dashboard/appointments` (previously `/appointments`)
- `/dashboard/appointments/:id/reschedule` (previously `/appointments/:id/reschedule`)
- `/dashboard/book` (previously `/book`)
- `/dashboard/loyalty` (previously `/loyalty`)
- `/dashboard/profile` (previously `/profile`)
- `/dashboard/request-appointment` (previously `/request-appointment`)

All `/dashboard/*` routes SHALL have `meta: { requiresAuth: true }`.

#### Scenario: Unauthenticated access to /dashboard/* redirects to login
- **WHEN** an unauthenticated visitor navigates to any `/dashboard/*` path
- **THEN** the router redirects them to `/login`

#### Scenario: Authenticated access to /dashboard/* succeeds
- **WHEN** an authenticated user navigates to `/dashboard/appointments`
- **THEN** the Appointments page renders normally

### Requirement: /doctors and /doctors/:id/slots are accessible without authentication
The system SHALL remove the `requiresAuth` guard from the `/doctors` and `/doctors/:id/slots` routes so guests can browse doctors and available slots.

#### Scenario: Guest can view the doctors list
- **WHEN** an unauthenticated visitor navigates to `/doctors`
- **THEN** the DoctorsView renders without redirecting to login

#### Scenario: Clicking Continue on slot selection navigates to /dashboard/book (triggers auth guard)
- **WHEN** a guest selects a slot and clicks "Continue"
- **THEN** they are navigated to `/dashboard/book` which the router's auth guard redirects to `/login`
