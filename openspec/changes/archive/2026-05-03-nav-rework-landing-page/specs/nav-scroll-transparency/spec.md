## ADDED Requirements

### Requirement: Navbar is transparent over the landing page hero and transitions to solid on scroll
The system SHALL render `AppNavbar.vue` with a fully transparent background (`bg-transparent`) when the page scroll position is less than 80 px. Once the visitor scrolls 80 px or more, the navbar background SHALL transition to `bg-clinic-dark` with a smooth CSS transition (`transition-colors duration-300`). A `backdrop-blur-sm` class SHALL be applied at all times to maintain text legibility over the hero image. This transparent-initial behaviour SHALL only apply when the current route is the landing page (`/`). On all other pages the navbar SHALL always render with `bg-clinic-dark` regardless of scroll position.

#### Scenario: Navbar is transparent at the top of the landing page
- **WHEN** a visitor loads the landing page and has not scrolled
- **THEN** the navbar renders with a transparent background over the hero image

#### Scenario: Navbar becomes solid after scrolling 80 px
- **WHEN** a visitor scrolls down at least 80 px on the landing page
- **THEN** the navbar background changes to `bg-clinic-dark` with a smooth colour transition

#### Scenario: Navbar is always solid on non-landing pages
- **WHEN** a visitor views any page other than the landing page (`/`)
- **THEN** the navbar renders with `bg-clinic-dark` regardless of scroll position

### Requirement: useScrolled composable tracks whether the page has been scrolled past a threshold
The system SHALL provide a `useScrolled(threshold: number)` composable at `resources/spa/composables/useScrolled.ts`. It SHALL attach a passive `scroll` listener to `window` on mount and remove it on unmount, returning a readonly reactive boolean `scrolled` that is `true` when `window.scrollY >= threshold` and `false` otherwise.

#### Scenario: Composable returns false at page top
- **WHEN** the composable is used and the page has not been scrolled
- **THEN** `scrolled` is `false`

#### Scenario: Composable returns true after threshold
- **WHEN** the page is scrolled past the configured threshold
- **THEN** `scrolled` is `true`
