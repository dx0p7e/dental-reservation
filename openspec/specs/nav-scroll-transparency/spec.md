## ADDED Requirements

### Requirement: Navbar is transparent over the landing page hero and transitions to solid on scroll
The system SHALL render `AppNavbar.vue` with a fully transparent background (`bg-transparent`) when the page scroll position is less than 1 px (effectively at the very top). Once the visitor scrolls at all, the navbar background SHALL transition to `bg-clinic-dark` with a smooth CSS transition (`transition-colors duration-300`). A `backdrop-blur-sm` class SHALL be applied when transparent to maintain text legibility over the hero image. This transparent-initial behaviour SHALL only apply when the current route is the landing page (`/`). On all other pages the navbar SHALL always render with `bg-clinic-dark` regardless of scroll position.

The navbar SHALL be `position: fixed` (not sticky) so it overlays the hero image without causing layout recalculation flicker on first scroll. A `<div class="h-[72px]">` spacer SHALL be rendered below the navbar on all non-landing pages to compensate for the fixed positioning.

#### Scenario: Navbar is transparent at the top of the landing page
- **WHEN** a visitor loads the landing page and has not scrolled
- **THEN** the navbar renders with a transparent background over the hero image

#### Scenario: Navbar becomes solid after any scroll
- **WHEN** a visitor scrolls down at all on the landing page
- **THEN** the navbar background changes to `bg-clinic-dark` with a smooth colour transition

#### Scenario: Navbar is always solid on non-landing pages
- **WHEN** a visitor views any page other than the landing page (`/`)
- **THEN** the navbar renders with `bg-clinic-dark` regardless of scroll position

#### Scenario: Scrolling back to top restores transparency
- **WHEN** a visitor scrolls back to `scrollY = 0` on the landing page
- **THEN** the navbar returns to transparent background

### Requirement: useScrolled composable tracks whether the page has been scrolled past a threshold
The system SHALL provide a `useScrolled(threshold: number)` composable at `resources/spa/composables/useScrolled.ts`. It SHALL attach a passive `scroll` listener to `window` on mount and remove it on unmount, returning a readonly reactive boolean `scrolled` that is `true` when `window.scrollY >= threshold` and `false` otherwise. The composable SHALL call the handler once on mount to initialise the value.

#### Scenario: Composable returns false at page top
- **WHEN** the composable is used and the page has not been scrolled
- **THEN** `scrolled` is `false`

#### Scenario: Composable returns true after threshold
- **WHEN** the page is scrolled past the configured threshold
- **THEN** `scrolled` is `true`

#### Scenario: Scroll listener is removed on unmount
- **WHEN** the component using the composable is unmounted
- **THEN** the scroll listener is removed from `window`
