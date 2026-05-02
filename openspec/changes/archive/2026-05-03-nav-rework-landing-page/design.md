## Context

`AppNavbar.vue` is a single shared component used across every public SPA page. It currently has a static `bg-clinic-dark` background, `py-4` vertical padding, and uses `route.path`-based `isActive()` for link highlighting. Nav links are `<RouterLink>` components that always trigger route navigation.

`LandingView.vue` is a long-scroll page that already contains Heroes, Services, Why-Us, How-It-Works, Testimonials, Doctors, and CTA sections. The About, Contact, and Loyalty content currently lives on separate routes (`/about`, `/contact`, `/loyalty`). There is no in-page anchor scroll infrastructure.

The project uses Vue 3 (Composition API), Vue Router 4, Tailwind v4, and the `@spa` alias for the SPA source root. Composables live in `resources/spa/composables/`.

## Goals / Non-Goals

**Goals:**
- Navbar starts fully transparent over the hero and transitions to `bg-clinic-dark` after 80 px of scroll.
- Navbar is slightly taller (`py-5` desktop) with minor visual polish (tracking, hover refinements).
- On the landing page (`route.name === 'landing'` or `route.path === '/'`), nav links are `<a href="#section-id">` anchors with smooth CSS scroll; on all other pages they remain `<RouterLink>` route links.
- Active nav link is determined by an IntersectionObserver watching section IDs when on the landing page; elsewhere the existing route-path logic applies.
- `LandingView.vue` gains three new sections: `#loyalty`, `#about`, `#contact`.
- The contact form in `#contact` uses the same POST logic already in `ContactView.vue`.

**Non-Goals:**
- No changes to standalone `/about`, `/contact`, `/loyalty` routes — they remain fully functional.
- No mobile hamburger menu redesign in this change.
- No backend changes.
- No SSR concerns (SPA-only).

## Decisions

### 1. Scroll transparency via `useScrolled` composable
A lightweight `useScrolled(threshold: number)` composable adds a `scroll` event listener on `window` (passive) and returns a reactive boolean `scrolled`. `AppNavbar.vue` imports it and toggles `bg-clinic-dark` / `bg-transparent` plus `backdrop-blur-sm` as a fallback for readability above the hero.

**Alternative considered:** CSS `position: sticky` with a parent scroll container sentinel element — rejected because it requires DOM structure changes across all pages.

### 2. Conditional link mode (anchor vs. route)
`AppNavbar.vue` receives no props. Instead it reads `useRoute().path === '/'` to decide whether to render each link as a native `<a href="#section-id">` (landing page) or a `<RouterLink :to="link.to">` (other pages). The `publicLinks` array gains an optional `anchorId` field.

**Alternative considered:** Emit events from `LandingView` to update a navbar store — rejected as over-engineered; the route check is sufficient.

### 3. Active section tracking with IntersectionObserver
A `useActiveSection(sectionIds: string[])` composable creates one `IntersectionObserver` with `threshold: 0.5` and `rootMargin: '-20% 0px -70% 0px'` to track which section is most in view. `AppNavbar.vue` uses this composable only when on the landing page; otherwise falls back to route-path matching.

**Alternative considered:** Scroll position arithmetic (compare `scrollY` to `offsetTop` of each section) — rejected because IntersectionObserver is more performant and handles variable viewport sizes better.

### 4. Contact form reuse in LandingView
Rather than extracting a `ContactForm.vue` component, `LandingView.vue` inlines the form state (matches `ContactView.vue` implementation) in the `#contact` section. The form already has a self-contained state machine (idle → submitting → success | error). Duplication is acceptable here; the form is simple and the standalone page is independent.

**Alternative considered:** Extract shared `<ContactForm>` component — valid but adds a file purely to avoid ~40 lines of duplication in a section that is unlikely to diverge.

## Risks / Trade-offs

- **IntersectionObserver accuracy at small viewports** → Short sections may not reach the 50 % visibility threshold. Mitigation: adjust `rootMargin` during implementation; fallback to last-passed section logic if needed.
- **Transparent navbar over non-hero pages** → The transparent state only applies to the landing page (guarded by `route.path === '/'`). On all other pages the navbar stays solid `bg-clinic-dark` immediately.
- **Contact form duplicated** → If the contact endpoint or validation rules change, both `ContactView.vue` and `LandingView.vue` need updates. Risk is low given the form's simplicity; tracked in tasks.
- **Smooth scroll browser support** → `scroll-behavior: smooth` is set globally in CSS (already present in most Tailwind setups). If not, add `html { scroll-behavior: smooth; }` to the global stylesheet.
