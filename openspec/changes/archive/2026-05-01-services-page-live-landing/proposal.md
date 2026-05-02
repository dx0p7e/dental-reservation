## Why

The main navbar has no "Services" link, making clinic services undiscoverable for new visitors. The landing page services section is hardcoded with fake placeholder data, so any real service added in the admin panel never appears on the public site.

## What Changes

- **New public page `/services`** (`ServicesView.vue`) — full-width services listing with hero header, responsive card grid, and loading/empty/error states; each card shows name, duration, price, description, and a "Book now →" link to `/doctors`
- **Navbar gains a "Paslaugos" link** (Lithuanian, consistent with existing labels) visible to guests and authenticated users alike
- **LandingView.vue services section** switches from a hardcoded array to a live `GET /api/v1/services` fetch; icons are removed from landing cards (API data carries no icon field); if more than 6 services are returned only the first 6 are shown with a "View all services →" link to `/services`
- **Router** gets a new public route `{ path: '/services', component: () => import('@spa/views/ServicesView.vue') }` — no `requiresAuth` guard, no `name` field (consistent with existing routes)

## Capabilities

### New Capabilities
- `services-page`: Dedicated public page at `/services` listing all clinic services via the existing `GET /api/v1/services` API with loading, empty, and error states

### Modified Capabilities
- `spa-landing-page`: Services section data source changes from hardcoded array to live API; icon column removed from landing cards; "View all services" overflow link added
- `public-navbar`: "Paslaugos" nav link added alongside the existing five public links

## Impact

- `resources/spa/views/ServicesView.vue` — new file
- `resources/spa/views/LandingView.vue` — services section rewritten; lucide icon imports for `Smile`, `ShieldCheck`, `Zap` removed if no longer used elsewhere in the file
- `resources/spa/router/index.ts` — one new route entry
- `resources/spa/components/AppNavbar.vue` — one new link added to `publicLinks` array
- No backend changes — `GET /api/v1/services` already exists and is public
- No new dependencies
