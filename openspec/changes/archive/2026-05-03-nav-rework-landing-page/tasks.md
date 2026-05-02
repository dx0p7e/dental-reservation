## 1. Composables

- [x] 1.1 Create `resources/spa/composables/useScrolled.ts` — passive `scroll` listener on `window`, returns reactive `scrolled: boolean` based on `window.scrollY >= threshold`
- [x] 1.2 Create `resources/spa/composables/useActiveSection.ts` — IntersectionObserver watching given section IDs, returns reactive `activeSection: string | null`

## 2. AppNavbar — Scroll Transparency & Height

- [x] 2.1 Import and call `useScrolled(80)` in `AppNavbar.vue`
- [x] 2.2 Apply conditional background: `bg-transparent backdrop-blur-sm` when not scrolled and on landing page; `bg-clinic-dark` otherwise; add `transition-colors duration-300`
- [x] 2.3 Increase navbar padding from `py-4` to `py-5`
- [x] 2.4 Update logo to `text-2xl tracking-tight`
- [x] 2.5 Update nav link hover style to `hover:text-clinic-teal` and add `tracking-wide`

## 3. AppNavbar — Anchor Links & Active Section

- [x] 3.1 Add `anchorId` field to `publicLinks` array entries (`hero`, `services`, `loyalty`, `about`, `contact`)
- [x] 3.2 Conditionally render nav links: `<a :href="'#' + link.anchorId">` when `route.path === '/'`, else `<RouterLink :to="link.to">`
- [x] 3.3 Import and call `useActiveSection(['hero','services','loyalty','about','contact','doctors'])` in `AppNavbar.vue`
- [x] 3.4 Update `isActive()` logic: use `activeSection.value === link.anchorId` when on landing page; keep existing route-path logic elsewhere

## 4. LandingView — Section IDs & Smooth Scroll

- [x] 4.1 Add `id="hero"` to the hero `<section>`
- [x] 4.2 Add `id="services"` to the services `<section>`
- [x] 4.3 Add `id="doctors"` to the doctors `<section>`
- [x] 4.4 Add `id="testimonials"` to the testimonials `<section>`
- [x] 4.5 Ensure `scroll-behavior: smooth` is set — add `scroll-smooth` class to the root `<div>` in `LandingView.vue` (or to `<html>` in the global CSS)

## 5. LandingView — Loyalty Preview Section

- [x] 5.1 Add `<section id="loyalty" ...>` after the How-It-Works section with `bg-clinic-surface` background
- [x] 5.2 Render heading and introductory line (i18n keys: `landing.loyalty.title`, `landing.loyalty.subtitle`)
- [x] 5.3 Render three tier cards (Standard 0 pts / 0%, Silver 500 pts / 10%, Gold 1500 pts / 20%); highlight Gold with `border-clinic-teal`
- [x] 5.4 Add i18n keys for loyalty section to both `lt` and `en` locale files

## 6. LandingView — About Us Preview Section

- [x] 6.1 Add `<section id="about" ...>` after the loyalty section with `bg-white` background
- [x] 6.2 Render heading and 2–3 sentence body (i18n keys: `landing.about.title`, `landing.about.body`)
- [x] 6.3 Add "Learn more →" `RouterLink` to `/about`
- [x] 6.4 Add i18n keys for about section to both `lt` and `en` locale files

## 7. LandingView — Contact Form Section

- [x] 7.1 Add `<section id="contact" ...>` after the about section with `bg-clinic-surface` background
- [x] 7.2 Add section heading (i18n key: `landing.contact.title`)
- [x] 7.3 Add static contact info (address, phone, email — same constants as `ContactView.vue`) on the left column
- [x] 7.4 Add form fields (name, email, subject, message textarea) and "Siųsti" submit button on the right column
- [x] 7.5 Add form state refs (`contactForm`, `contactLoading`, `contactSuccess`, `contactErrors`) and `submitContactForm()` method posting to `/api/v1/contact`
- [x] 7.6 Show success message on 200 response; show inline field errors on 422; handle generic error
- [x] 7.7 Add i18n keys for contact section to both `lt` and `en` locale files

## 8. Tests

- [x] 8.1 Add unit test for `useScrolled` composable — verify reactive value changes with mocked `scrollY`
- [x] 8.2 Add unit test for `useActiveSection` composable — verify `activeSection` updates when IntersectionObserver fires
- [x] 8.3 Add/update feature test asserting landing page renders `#loyalty`, `#about`, `#contact` sections
- [x] 8.4 Run `php artisan test --compact` to confirm all tests pass
