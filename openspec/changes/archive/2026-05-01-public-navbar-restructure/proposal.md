## Why

The current navbar conflates public site navigation with personal user pages: guests see "My Appointments" and "Loyalty" (authenticated dashboard links) before signing in, and the site lacks expected public pages (About Us, Contact Us). Personal user pages also live at flat top-level paths (`/appointments`, `/loyalty`) with no clear namespace separation, making auth guards and link management fragile as the SPA grows.

## What Changes

- **Route restructure** — all personal user pages move to a `/dashboard/` prefix:
  - `/appointments` → `/dashboard/appointments`
  - `/appointments/:id/reschedule` → `/dashboard/appointments/:id/reschedule`
  - `/book` → `/dashboard/book`
  - `/loyalty` → `/dashboard/loyalty`
  - `/profile` → `/dashboard/profile`
  - `/request-appointment` → `/dashboard/request-appointment`
  - All `/dashboard/*` routes keep `meta: { requiresAuth: true }`
- **`/doctors` and `/doctors/:id/slots` made public** — currently auth-guarded, they will become public pages (visitors should be able to browse doctors before committing to log in)
- **AppNavbar.vue rewrite** — left side: logo + public links (Home, Services, Doctors, Loyalty, About Us, Contact Us, Book); right side guest: Log In + Register; right side authenticated: username dropdown with personal links + logout
- **New public page `/loyalty`** — marketing page explaining the loyalty programme, tiers, and CTA; the existing `LoyaltyView.vue` (auth dashboard) moves to `/dashboard/loyalty`
- **New public page `/about`** — static clinic info + doctor cards + thesis technology note
- **New page `/contact`** — static contact info + unauthenticated contact form
- **New backend** — `POST /api/v1/contact` route (rate-limited, no auth), `ContactFormMail` mailable, sends to `config('app.contact_email')`
- **Internal link updates** — all `RouterLink` and `router.push()` calls to the moved routes updated across all SPA pages

## Capabilities

### New Capabilities

- `public-navbar`: Restructured navbar with public nav links, authenticated user dropdown, and `/dashboard/` route namespace for personal pages
- `spa-public-loyalty-marketing`: New public `/loyalty` marketing page explaining the loyalty programme and tier benefits to unauthenticated visitors
- `spa-about-page`: New static `/about` page with clinic info, doctor section, and technology note
- `spa-contact-page`: New `/contact` page with contact form (no auth); includes backend `POST /api/v1/contact` endpoint and `ContactFormMail` mailable

### Modified Capabilities

- `spa-appointments-ui`: Route changes from `/appointments` (and `/appointments/:id/reschedule`) to `/dashboard/appointments` (and `/dashboard/appointments/:id/reschedule`)
- `spa-booking-ui`: Route changes from `/book` to `/dashboard/book`; the "Book" navbar link routes to `/dashboard/book` when authenticated, `/login` when guest
- `spa-loyalty-ui`: Route changes from `/loyalty` to `/dashboard/loyalty`; the existing `LoyaltyView.vue` (dashboard) moved under `/dashboard/`

## Impact

- **Frontend routes** — router `index.ts` gains `/dashboard/*` routes; old top-level personal routes removed
- **All SPA views** that link to `/appointments`, `/book`, `/loyalty`, `/profile` need updated paths
- **AppNavbar.vue** — full rewrite
- **New Vue views** — `LoyaltyMarketingView.vue`, `AboutView.vue`, `ContactView.vue`
- **Backend** — new `POST /api/v1/contact` route + `ContactFormMail` mailable
- **No database changes**
- **No Filament/admin/doctor panel changes**
