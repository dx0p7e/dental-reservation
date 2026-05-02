## Context

The SPA has a flat route structure where personal authenticated pages (`/appointments`, `/book`, `/loyalty`, `/profile`) live at the same level as public pages (`/`, `/login`, `/register`). The current `AppNavbar.vue` renders personal links unconditionally, exposing "My Appointments" and "Loyalty" to unauthenticated visitors who cannot use them. Additionally, `/doctors` is currently auth-guarded but logically should be public (browsing doctors is a pre-login action). The site is missing the public informational pages (About, Contact) and a public loyalty marketing page needed to explain the programme to guests.

## Goals / Non-Goals

**Goals:**
- Introduce a `/dashboard/` route namespace for all personal authenticated pages; enforce `requiresAuth: true` via existing router guard
- Make `/doctors` and `/doctors/:id/slots` public (remove auth guard)
- Rewrite `AppNavbar.vue` with a clean public links + authenticated dropdown pattern
- Add three new public pages: `/loyalty` (marketing), `/about`, `/contact`
- Add backend `POST /api/v1/contact` with rate limiting and `ContactFormMail`

**Non-Goals:**
- Mobile hamburger menu / responsive drawer
- Animated mega-menu or mega-nav
- CMS-driven About Us content
- Storing contact form submissions in the database
- `/services` route (not currently in router — out of scope for this change)

## Decisions

### D1 — `/dashboard/` prefix for personal pages
**Decision**: All personal pages move to `/dashboard/appointments`, `/dashboard/book`, `/dashboard/loyalty`, `/dashboard/profile`, `/dashboard/request-appointment`. The prefix makes the auth boundary explicit, groups all routes requiring `requiresAuth: true` under a single namespace, and simplifies future middleware grouping.

**Alternative considered**: Keep flat structure and add auth checks only to components. Rejected — this makes auth intent invisible in the router and requires per-component checks.

### D2 — Make `/doctors` and `/doctors/:id/slots` public
**Decision**: Remove `meta: { requiresAuth: true }` from both routes. Visitors should be able to browse available doctors and their slots as marketing content before deciding to register.

**Alternative considered**: Keep them auth-guarded and add a teaser/preview on the landing page. Rejected — the booking flow starts from doctors, so guarding it creates unnecessary friction.

### D3 — New `LoyaltyMarketingView.vue` at `/loyalty`; existing `LoyaltyView.vue` moves to `/dashboard/loyalty`
**Decision**: The existing `LoyaltyView.vue` (authenticated dashboard with live data) is renamed `LoyaltyDashboardView.vue` and moved to `/dashboard/loyalty`. A new `LoyaltyMarketingView.vue` is created at `/loyalty` with static tier info and CTAs for guests.

**Alternative considered**: Show a hybrid page at `/loyalty` that conditionally renders the dashboard for auth users. Rejected — the marketing page and dashboard page serve different audiences and mixing them creates complexity.

### D4 — Username dropdown via `ref<boolean>` + `@click.outside`
**Decision**: The authenticated user dropdown in `AppNavbar.vue` uses a `ref<boolean> dropdownOpen` toggled on username click, and closes via a `@click.outside` custom directive (if already in the project) or a document `mousedown` listener in an `onMounted` hook.

**Alternative considered**: Use a native `<details>`/`<summary>` element for the dropdown (no JS needed). Accepted as simpler but the `<details>` element is harder to style consistently with the clinic design system; the `ref` approach is standard in the codebase.

### D5 — Contact form sends email, no DB storage
**Decision**: `POST /api/v1/contact` validates the request, creates a `ContactFormMail` mailable, and sends to `config('app.contact_email')` (env `CONTACT_EMAIL`). No database table is used.

**Alternative considered**: Store submissions in a `contact_messages` table and send from a queued job. Rejected as over-engineering for a thesis project — a synchronous `Mail::to()->send()` suffices.

### D6 — Rate limit: `throttle:5,1` on contact endpoint
**Decision**: Apply `throttle:5,1` middleware (5 requests per 1 minute per IP) to prevent form abuse. No authentication required.

## Risks / Trade-offs

- **Link rot risk** — Any hardcoded `/appointments`, `/loyalty`, `/book`, `/profile` paths in SPA views will silently break after the route rename. All views must be audited for `router.push()` and `RouterLink to=` references. [Risk] → Grep for each old path during implementation.
- **`/doctors` now public** — Slot data becomes visible without authentication. The booking confirmation at `/dashboard/book` still requires auth. This is intentional and mirrors how healthcare booking sites operate.
- **Contact form email delivery** — Depends on `MAIL_*` env configuration. On environments without SMTP configured, the send will fail silently unless queue workers are checked. [Risk] → Add a `try/catch` in the controller and return a generic error response.
- **`LoyaltyMarketingView.vue` has hardcoded tier data** — Tier thresholds and discount percentages (0%, 10%, 20%) are currently pulled from the database; the marketing page duplicates them as constants. If tier configuration changes, the marketing page must be manually updated. [Risk] → Document this coupling with a comment.

## Migration Plan

1. Update router — add `/dashboard/*` routes, remove old personal routes, remove auth guard from `/doctors` and `/doctors/:id/slots`
2. Audit and update all `RouterLink` and `router.push()` references in all SPA views
3. Rewrite `AppNavbar.vue`
4. Create `LoyaltyMarketingView.vue` at `/loyalty`
5. Rename `LoyaltyView.vue` → `LoyaltyDashboardView.vue`; update its import in router
6. Create `AboutView.vue` and `ContactView.vue`
7. Add `POST /api/v1/contact` route + `ContactController@store` + `ContactFormMail`
8. Add `CONTACT_EMAIL` to `.env.example`

**Rollback**: All changes are frontend routes and new files. Roll back by reverting the router and navbar. No database migrations means zero migration rollback needed.

## Open Questions

- Should `/doctors/:id/slots` (slot selection) remain public or redirect to login when the user tries to click "Continue" (book)? Current plan: page is public, but "Continue" navigates to `/dashboard/book` which triggers the auth guard. This is clean — confirm no objections.
- `config('app.contact_email')` — should this fall back to `config('mail.from.address')` if not set? Recommend yes as the safer default.
