## Why

The current landing page and navigation bar treat the site as a multi-page app: each nav link routes to a separate page (About, Contact, Loyalty), fragmenting what should be a cohesive one-page marketing experience. The navbar is also visually flat — a solid dark bar from the top — which competes with the hero image and lacks polish. This change consolidates the public-facing experience into a single, scroll-driven landing page and refines the navbar to feel sleeker.

## What Changes

- **Navbar becomes transparent on load, transitions to `bg-clinic-dark` after scrolling ~80 px** — uses a `useScrolled` composable or inline scroll watcher.
- **Navbar height is increased** — padding increases from `py-4` to `py-5` (desktop).
- **Navbar receives visual polish** — sharper letter-spacing, refined hover states, slightly larger logo text.
- **Nav links on the landing page smooth-scroll to in-page anchor sections** instead of navigating to separate routes. On all other pages the links continue to route normally.
- **Active nav link highlights based on the currently visible landing page section** (IntersectionObserver) instead of route-path matching.
- **Landing page gains a "Loyalty" preview section** — brief tier summary (Standard / Silver / Gold) with a CTA to `/loyalty`.
- **Landing page gains an "About Us" preview section** — 2–3 sentences about the clinic with a link to `/about`.
- **Contact form is moved onto the landing page** (`#contact` anchor) — the standalone `/contact` page remains for direct URL access but the landing page also renders the full contact form (name, email, subject, message fields; POST `/api/v1/contact`).

## Capabilities

### New Capabilities
- `nav-scroll-transparency`: Navbar starts transparent and transitions to solid `bg-clinic-dark` after a configurable scroll threshold. Includes a scroll composable usable by `AppNavbar.vue`.

### Modified Capabilities
- `public-navbar`: Taller height, transparent-to-solid scroll behaviour, smooth-scroll anchor links when on the landing page, active-section tracking via IntersectionObserver replacing route-path `isActive()`.
- `spa-landing-page`: Add loyalty preview section (`#loyalty`), add about-us preview section (`#about`), embed contact form section (`#contact`). Nav anchor IDs added to existing sections: `#services`, `#how-it-works`, `#doctors`.

## Impact

- `resources/spa/components/AppNavbar.vue` — scroll behaviour, height, polish, conditional link mode (anchor vs route).
- `resources/spa/views/LandingView.vue` — three new sections; existing sections gain `id` attributes; section intersection watcher added.
- `resources/spa/composables/` — new `useScrolled.ts` composable (scroll threshold watcher).
- No backend changes required; contact form already POSTs to the existing `/api/v1/contact` endpoint.
- No route additions required; standalone About/Contact pages remain unchanged.
