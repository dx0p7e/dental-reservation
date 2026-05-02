## 1. Router Refactor

- [x] 1.1 Move `/appointments` and `/appointments/:id/reschedule` routes to `/dashboard/appointments` and `/dashboard/appointments/:id/reschedule` with `meta: { requiresAuth: true }`
- [x] 1.2 Move `/book` route to `/dashboard/book` with `meta: { requiresAuth: true }`
- [x] 1.3 Move `/loyalty` route component (existing `LoyaltyView`) to `/dashboard/loyalty` with `meta: { requiresAuth: true }`
- [x] 1.4 Move `/profile` route to `/dashboard/profile` with `meta: { requiresAuth: true }`
- [x] 1.5 Move `/request-appointment` route to `/dashboard/request-appointment` with `meta: { requiresAuth: true }`
- [x] 1.6 Remove `meta: { requiresAuth: true }` from `/doctors` and `/doctors/:id/slots`
- [x] 1.7 Add new `/loyalty` route pointing to `LoyaltyMarketingView.vue` (no auth guard)
- [x] 1.8 Add new `/about` route pointing to `AboutView.vue` (no auth guard)
- [x] 1.9 Add new `/contact` route pointing to `ContactView.vue` (no auth guard)

## 2. Navbar Rewrite

- [x] 2.1 Rewrite `AppNavbar.vue` — always-visible public links: Home, Doctors, Loyalty, About Us, Contact Us, Book
- [x] 2.2 "Book" link logic: navigates to `/dashboard/book` if authenticated, `/login` if guest
- [x] 2.3 Guest right-side: "Log In" link (`/login`) and "Register" button (`/register`)
- [x] 2.4 Authenticated right-side: username trigger with dropdown (Rezervuoti, Mano vizitai, Mano lojalumas, Mano profilis, divider, Atsijungti)
- [x] 2.5 Dropdown close-on-outside-click behaviour (document `mousedown` listener or `v-click-outside`)
- [x] 2.6 Atsijungti action clears auth session (POST `/api/v1/auth/logout` or existing logout endpoint) and redirects to `/login`

## 3. New Public Views

- [x] 3.1 Create `LoyaltyMarketingView.vue` at `/loyalty` — hero, how-it-works 3-step, tier cards (Standard/Silver/Gold), CTA
- [x] 3.2 CTA in `LoyaltyMarketingView.vue` links to `/register` for guests and `/dashboard/loyalty` for authenticated users
- [x] 3.3 Create `AboutView.vue` at `/about` — clinic info section, doctors team section (fetched from `GET /api/v1/doctors`), technology note
- [x] 3.4 Handle API failure in `AboutView.vue` doctors section — show empty state without crashing
- [x] 3.5 Create `ContactView.vue` at `/contact` — static contact info + form (name, email, subject, message)
- [x] 3.6 `ContactView.vue` form submit POSTs to `/api/v1/contact`, shows success state on 200, shows inline errors on 422

## 4. Rename / Update Existing SPA Views

- [x] 4.1 Rename `LoyaltyView.vue` → `LoyaltyDashboardView.vue` and update router import
- [x] 4.2 Update `AppointmentsView.vue` empty-state booking link from `/book` → `/dashboard/book`
- [x] 4.3 Update `DoctorSlotsView.vue` — "Continue" button navigates to `/dashboard/book` (was `/book`)
- [x] 4.4 Update `DoctorSlotsView.vue` empty-state request-appointment link from `/request-appointment` → `/dashboard/request-appointment`
- [x] 4.5 Update `LandingView.vue` — change all `RouterLink to="/appointments"` (×4) to `/dashboard/appointments` and `to="/loyalty"` to `/dashboard/loyalty`
- [x] 4.6 Update `LoginView.vue` — change `router.push('/appointments')` (post-login redirect) to `/dashboard/appointments`
- [x] 4.7 Update `RegisterView.vue` — change `router.push('/appointments')` (post-register redirect) to `/dashboard/appointments`
- [x] 4.8 Update `BookView.vue` — change `router.push('/appointments')` (post-confirm redirect) to `/dashboard/appointments`; change `RouterLink to="/profile"` to `/dashboard/profile`
- [x] 4.9 Update `RescheduleView.vue` — change `router.push('/appointments?rescheduled=1')` to `/dashboard/appointments?rescheduled=1`; change `RouterLink to="/appointments"` to `/dashboard/appointments`
- [x] 4.10 Update `RequestBookingView.vue` — change `router.push('/appointments')` to `/dashboard/appointments`; change `RouterLink to="/profile"` to `/dashboard/profile`

## 5. Backend — Contact Form

- [x] 5.1 Create `ContactFormMail` mailable (`php artisan make:mail ContactFormMail`) with subject, sender name, and message body
- [x] 5.2 Create `ContactController` with `store` method — validate (name, email, subject, message); wrap `Mail::to(config('app.contact_email', config('mail.from.address')))->send(new ContactFormMail($data))` in a `try/catch (\Throwable $e)` block; return HTTP 200 `{"message": "Sent"}` on success, HTTP 500 `{"message": "Failed to send. Please try again later."}` on failure
- [x] 5.3 Add `POST /api/v1/contact` route in `routes/api.php` with `throttle:5,1` and no auth middleware
- [x] 5.4 Add `CONTACT_EMAIL` to `.env.example` with a placeholder value
- [x] 5.5 Use `config('app.contact_email', config('mail.from.address'))` as the recipient in `ContactController`
- [x] 5.6 Add `contact_email` key to `config/app.php` reading from `env('CONTACT_EMAIL')`

## 6. Tests

- [x] 6.1 Write Pest feature test: guest can access `/doctors` and `/doctors/1/slots` without auth (200 OK via API or router guard assertion)
- [x] 6.2 Write Pest feature test: unauthenticated GET to `/api/v1/contact` equivalent routes are not accessible (405 or route not found)
- [x] 6.3 Write Pest feature test: `POST /api/v1/contact` with valid data sends mail and returns 200
- [x] 6.4 Write Pest feature test: `POST /api/v1/contact` with missing fields returns 422
- [x] 6.5 Write Pest feature test: `POST /api/v1/contact` rate limiting returns 429 after 5 requests from same IP

## 7. Code Quality

- [x] 7.1 Run `vendor/bin/pint --dirty --format agent` to fix PHP code style on all changed files
- [x] 7.2 Run `php artisan test --compact` and confirm all tests pass
