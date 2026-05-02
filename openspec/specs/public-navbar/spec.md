## ADDED Requirements

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

### Requirement: Navbar height is increased and visually polished
The system SHALL render the navbar with increased vertical padding (`py-5` desktop, unchanged on mobile) compared to the previous `py-4`. The logo text SHALL use `text-2xl` (up from `text-xl`) with slightly tighter letter spacing (`tracking-tight`). Nav link text SHALL use `tracking-wide` and a subtle `hover:text-clinic-teal` transition instead of `hover:text-white`. The overall appearance SHALL feel sleeker and more refined while remaining consistent with the clinic's dark-teal design system.

#### Scenario: Navbar is taller than before
- **WHEN** a visitor views the navbar on a desktop viewport
- **THEN** the navbar has `py-5` vertical padding (taller than the previous `py-4`)

#### Scenario: Logo is larger and uses tight tracking
- **WHEN** a visitor views the navbar
- **THEN** the clinic name logo uses `text-2xl` and `tracking-tight`

### Requirement: Nav links smooth-scroll to landing page sections when on the landing page
The system SHALL render nav links as native `<a href="#section-id">` anchor elements (not `<RouterLink>`) when the current route is the landing page (`route.path === '/'`). Clicking a nav link on the landing page SHALL smooth-scroll to the corresponding section using the browser's built-in anchor scroll (requires `scroll-behavior: smooth` on the `<html>` element). The mapping of link labels to anchor IDs SHALL be:
- Home → `#hero`
- Services → `#services`
- Loyalty → `#loyalty`
- About → `#about`
- Doctors → `#doctors`
- Contact → `#contact`
- Reviews → `#testimonials`

On all other routes, the same links SHALL render as `<RouterLink>` components navigating to their respective pages.

#### Scenario: Nav links are anchors on the landing page
- **WHEN** a visitor is on the landing page (`/`)
- **THEN** clicking a nav link smooth-scrolls to the corresponding `#section-id` on the page without triggering a route change

#### Scenario: Nav links are RouterLinks on other pages
- **WHEN** a visitor is on any page other than `/`
- **THEN** nav links navigate to their respective routes

### Requirement: Active nav link reflects the currently visible landing page section
The system SHALL use an IntersectionObserver (via `useActiveSection` composable) to track which landing page section is currently most visible in the viewport when on the landing page. The nav link corresponding to the active section SHALL receive the active style (`text-clinic-teal border-b border-clinic-teal`). The `useActiveSection(sectionIds: string[])` composable SHALL be located at `resources/spa/composables/useActiveSection.ts` and SHALL return a reactive `activeSection` ref defaulting to the first section ID. A passive scroll listener SHALL reset `activeSection` to the first section when `scrollY < 50`. When the visitor is on any other route, the existing `route.path`-based `isActive()` logic continues to apply.

#### Scenario: Active section highlighted as user scrolls landing page
- **WHEN** a visitor scrolls the landing page and a section crosses the viewport threshold
- **THEN** the corresponding nav link changes to the active style (`text-clinic-teal`)

#### Scenario: Hero link is active at page top
- **WHEN** a visitor loads the landing page or scrolls back to the very top
- **THEN** the Home nav link is active

#### Scenario: No active section highlight on non-landing routes
- **WHEN** a visitor is on a non-landing page
- **THEN** the active nav link is determined by `route.path` matching, not IntersectionObserver
