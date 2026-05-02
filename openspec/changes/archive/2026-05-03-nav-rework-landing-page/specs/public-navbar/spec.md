## ADDED Requirements

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
- Contact → `#contact`

On all other routes, the same links SHALL render as `<RouterLink>` components navigating to their respective pages (`/services`, `/loyalty`, `/about`, `/contact`).

#### Scenario: Nav links are anchors on the landing page
- **WHEN** a visitor is on the landing page (`/`)
- **THEN** clicking a nav link smooth-scrolls to the corresponding `#section-id` on the page without triggering a route change

#### Scenario: Nav links are RouterLinks on other pages
- **WHEN** a visitor is on any page other than `/`
- **THEN** nav links navigate to their respective routes (`/services`, `/loyalty`, `/about`, `/contact`)

### Requirement: Active nav link reflects the currently visible landing page section
The system SHALL use an IntersectionObserver (via `useActiveSection` composable) to track which landing page section is currently most visible in the viewport when on the landing page. The nav link corresponding to the active section SHALL receive the active style (`text-clinic-teal border-b border-clinic-teal`). The `useActiveSection(sectionIds: string[])` composable SHALL be located at `resources/spa/composables/useActiveSection.ts` and SHALL return a reactive `activeSection` ref (string | null). When the visitor is on any other route, the existing `route.path`-based `isActive()` logic continues to apply.

#### Scenario: Active section highlighted as user scrolls landing page
- **WHEN** a visitor scrolls the landing page and a section crosses the viewport threshold
- **THEN** the corresponding nav link changes to the active style (`text-clinic-teal`)

#### Scenario: No active section highlight on non-landing routes
- **WHEN** a visitor is on a non-landing page
- **THEN** the active nav link is determined by `route.path` matching, not IntersectionObserver
