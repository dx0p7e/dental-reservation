## Why

The patient-facing SPA is functionally complete but visually bare — raw HTML tables, no branding, no design consistency. For a thesis demo and real-world credibility, the UI must look like a production dental clinic product, not a developer prototype. This change replaces all patient-facing views with a cohesive flat design system inspired by modern dental clinic websites.

## What Changes

- **New Tailwind CSS design tokens** — `clinic-dark`, `clinic-blue`, `clinic-teal`, `clinic-surface`, `clinic-border`, `clinic-text`, `clinic-muted` defined as CSS custom properties inside `@theme inline {}` in `resources/css/app.css`
- **LandingView.vue** — full rebuild with sticky dark navbar, full-viewport hero, services grid, stats row, how-it-works steps, testimonials, CTA section, and footer
- **DoctorSlotsView.vue** — redesigned with sidebar filters, slot card grid, loyalty discount badge, and empty-state inline message
- **BookView.vue** — redesigned as a centered confirmation card with strikethrough pricing and savings callout
- **RequestBookingView.vue** — redesigned as a centered form card
- **AppointmentsView.vue** — card list replacing HTML table, inline status badges, cancel action
- **LoyaltyView.vue** — tier badge, points balance, teal progress bar, transaction history
- **New shared components** — `AppNavbar.vue`, `PageHeader.vue`, `StatusBadge.vue`
- No backend changes, no API changes, no Filament changes, no router changes, no Pinia store changes

## Capabilities

### New Capabilities

- `spa-design-system`: Tailwind CSS custom colour tokens, typography rules (Inter, tracking-tight headings), and shared UI components (AppNavbar, PageHeader, StatusBadge) that all patient pages consume
- `spa-landing-page`: Full rebuild of LandingView.vue — NavBar, HeroSection, ServicesSection, WhyUsSection, HowItWorksSection, TestimonialsSection, CTASection, Footer
- `spa-booking-ui`: Redesign of DoctorSlotsView.vue (sidebar + slot cards + loyalty badge + empty state) and BookView.vue (confirmation card with discount display)
- `spa-appointments-ui`: Redesign of AppointmentsView.vue from an HTML table to a card list with inline status badges and cancel action
- `spa-loyalty-ui`: Redesign of LoyaltyView.vue with tier badge, points balance, progress bar, and transaction history table
- `spa-request-booking-ui`: Redesign of RequestBookingView.vue as a centered form card with pre-filled service from query param

### Modified Capabilities

<!-- None — all existing specs describe backend/API behaviour which is unchanged. Visual presentation changes are implementation details, not spec-level requirement changes. -->

## Impact

- `resources/spa/views/` — all patient-facing SPA views rewritten (`LandingView`, `DoctorSlotsView`, `BookView`, `RequestBookingView`, `AppointmentsView`, `LoyaltyView`)
- `resources/spa/components/` — new shared components: `AppNavbar.vue`, `PageHeader.vue`, `StatusBadge.vue`
- `resources/css/app.css` — clinic colour tokens added to `@theme inline {}`; Inter font import added
- No PHP, no migrations, no routes, no policies, no Eloquent models, no Filament panels affected
- Wayfinder-generated route functions remain unchanged; all `import { ... } from '@/actions/'` and `@/routes/` imports are preserved
