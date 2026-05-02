## 1. Design Tokens & Font

- [x] 1.1 Add Inter font `@import` from Google Fonts to `resources/css/app.css` and add `Inter` as first value in `--font-sans`
- [x] 1.2 Add seven clinic colour tokens (`--color-clinic-dark`, `--color-clinic-blue`, `--color-clinic-teal`, `--color-clinic-surface`, `--color-clinic-border`, `--color-clinic-text`, `--color-clinic-muted`) inside the `@theme inline {}` block in `resources/css/app.css`
- [x] 1.3 Verify clinic token classes compile correctly (e.g. `bg-clinic-dark`, `text-clinic-teal`) by running `npm run build` and checking for no errors

## 2. Shared SPA Components

- [x] 2.1 Create `resources/spa/components/StatusBadge.vue`
- [x] 2.2 Create `resources/spa/components/PageHeader.vue`
- [x] 2.3 Create `resources/spa/components/AppNavbar.vue`

## 3. LandingView Rebuild

- [x] 3.1 Replace `LandingView.vue` content with the `AppNavbar` component at the top (or inline sticky nav)
- [x] 3.2 Add HeroSection inside `LandingView.vue`
- [x] 3.3 Add ServicesSection
- [x] 3.4 Add WhyUsSection
- [x] 3.5 Add HowItWorksSection
- [x] 3.6 Add TestimonialsSection
- [x] 3.7 Add CTASection
- [x] 3.8 Add Footer

## 4. DoctorSlotsView Redesign

- [x] 4.1 Add `<PageHeader>` with title "Book an Appointment" and breadcrumbs
- [x] 4.2 Restructure layout to two columns
- [x] 4.3 Render slot list as clickable cards
- [x] 4.4 Add loyalty discount badge below service select
- [x] 4.5 Add empty-state message when slot list is empty
- [x] 4.6 "Continue" button is disabled when no slot selected

## 5. BookView Redesign

- [x] 5.1 Replace `BookView.vue` layout with a centred card and a back link above it
- [x] 5.2 Add summary rows inside the card: Service, Doctor, Date & Time
- [x] 5.3 Add price row with discount display
- [x] 5.4 Add full-width "Confirm Booking" button

## 6. RequestBookingView Redesign

- [x] 6.1 Add `<PageHeader title="Request an Appointment" />` above the form
- [x] 6.2 Replace existing form layout with a centred card (`max-w-lg mx-auto border rounded-lg p-8`)
- [x] 6.3 Pre-fill service select from `service_id` query param on mount
- [x] 6.4 Disable "Submit Request" button when service or preferred date is not set

## 7. AppointmentsView Redesign

- [x] 7.1 Add `<PageHeader title="My Appointments" />` with "Request Appointment" and "Book Appointment" buttons in the heading row
- [x] 7.2 Replace HTML table with a card list — each appointment as `border rounded-lg p-4` card
- [x] 7.3 Add `<StatusBadge>` for each appointment card
- [x] 7.4 Show slot date/time when available, or "Awaiting confirmation" text when `slot` is null
- [x] 7.5 Show "Cancel" button only for `pending` and `confirmed` appointments; wire to cancel API call
- [x] 7.6 Add empty-state message ("No appointments yet" + booking link) when list is empty

## 8. LoyaltyView Redesign

- [x] 8.1 Add `<PageHeader title="Your Loyalty Status" />` at the top of `LoyaltyView.vue`
- [x] 8.2 Build status card: tier badge (colour by tier level), points balance (`text-4xl font-bold`), progress bar (`bg-clinic-teal` fill), next-tier label or "Maximum tier reached"
- [x] 8.3 Add "Transaction History" section heading and transaction rows (date, description, `+N pts` in teal or `−N pts` in red)
- [x] 8.4 Add empty-state for transactions ("No transactions yet")

## 9. Tests

- [x] 9.1 Write a Pest feature test that verifies the SPA shell route returns HTTP 200 (smoke test for the serving layer)
- [x] 9.2 Run `php artisan test --compact` and confirm all existing tests still pass after the frontend changes

## 10. Post-Ship Bug Fixes (found during manual testing)

- [x] 10.1 **Navbar user name disappears on refresh** — `AppNavbar.vue` displayed `authStore.user?.name` but the Pinia `user` ref was never persisted; on refresh token was restored from `localStorage` but `user` remained `null`. Fix: added `fetchUser()` action to auth store (calls `GET /api/auth/user`, writes `data.user` to `user.value`, clears token on 401) and called it in `router/index.ts` `beforeEach` guard when `auth.token && !auth.user`. Also removed stray `console.log` from `AppNavbar.vue`.
- [x] 10.2 **Dashboard route 500 — `Route [verification.notice] not defined`** — `/dashboard` carried `'verified'` middleware, which redirects to `route('verification.notice')` when a user's email is unverified. That route only exists when Fortify's `emailVerification` feature is enabled, but `config/fortify.php` has `'features' => []`. Fix: removed `'verified'` from the `/dashboard` route middleware in `routes/web.php`.
- [x] 10.3 **`fetchUser` hitting wrong URL** — axios instance has `baseURL: '/api/v1'`; an absolute-looking `/api/auth/user` path was still appended to it (axios strips the leading slash), producing `/api/v1/api/auth/user`. Fix: pass `{ baseURL: '/api' }` as a per-request config override so the path resolves as `/api/auth/user`.
