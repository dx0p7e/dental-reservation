## Context

The SPA has a working `GET /api/v1/services` endpoint (public, no auth required) that returns all services with `id`, `name`, `description`, `duration_minutes`, and `price`. The TypeScript `Service` interface in `resources/spa/types/index.ts` already matches this shape. The landing page services section currently renders a hardcoded three-item array with lucide icons — the icons are local constants and have no equivalent field in the API response.

The `AppNavbar.vue` `publicLinks` array is the single source of truth for all public nav links; adding one entry there is sufficient to expose the Services link everywhere.

## Goals / Non-Goals

**Goals:**
- Introduce `/services` as a fully-featured public page
- Replace the landing page's fake service cards with live data
- Add "Paslaugos" to the navbar (Lithuanian, consistent with all other public link labels)
- Keep backend completely untouched

**Non-Goals:**
- Per-service detail pages (`/services/:id`)
- Service filtering, search, or sorting on the services page
- Booking a specific service directly — all "Book" links point to `/doctors` (general entry point)
- Changing the `/dashboard/book` links elsewhere in LandingView.vue
- Adding a `name` field to the new router entry (no other routes use named routes)

## Decisions

### D1 — Drop icons from LandingView service cards

**Decision:** Remove the `<component :is="svc.icon">` row from the landing service cards when switching to live data.

**Rationale:** The API response has no icon field and there is no reliable way to map a service name to a lucide icon dynamically. The card layout reads cleanly without an icon — `name` + `duration · price` + `Book →` is sufficient for a landing preview. The icon was only added as decoration with hardcoded data.

**Alternative considered:** Ship a default icon (e.g. `Stethoscope`) for every landing card. Rejected — all cards would look identical and decorative parity adds no information value.

### D2 — 6-service cap with "View all" overflow on landing page

**Decision:** Slice the API response to the first 6 items on LandingView; show "View all services →" link to `/services` only when the total count exceeds 6.

**Rationale:** The landing page services section is a preview, not an exhaustive list. Six cards (3-column × 2 rows) fills the grid cleanly; more would push other page sections too far down.

### D3 — Route to `/doctors` from ServicesView cards

**Decision:** "Book now →" links on ServicesView route to `/doctors` (general doctor selection).

**Rationale:** `/dashboard/book` requires an authenticated user with a slot already selected in the booking store — navigating there from a services page would confuse guests. `/doctors` is the correct public booking entry point.

### D4 — Silent error fallback on LandingView, visible error on ServicesView

**Decision:** LandingView degrades to an empty services section on API failure (no error banner). ServicesView shows a teal-bordered inline alert with a "Retry" button.

**Rationale:** A broken landing page banner hurts first impressions significantly more than a quietly empty section. ServicesView is the authoritative page for services — users arriving there expect completeness and need a recovery path.

## Risks / Trade-offs

- **First-load flash on LandingView**: API call adds latency before service cards appear. Mitigation: 3 animated skeleton cards shown during fetch, matching the pattern used in other SPA views.
- **Icon removal changes landing aesthetics**: The existing cards have icons that add visual polish. Mitigation: Cards are still visually complete — name, duration/price badge, and link remain.
- **`duration_minutes` vs formatted string**: Hardcoded cards had `"45 min"` strings; live data has integer `duration_minutes`. The template formats it inline as `{{ svc.duration_minutes }} min`. No change to the API or types needed.
