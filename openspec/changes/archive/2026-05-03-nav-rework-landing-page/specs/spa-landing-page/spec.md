## ADDED Requirements

### Requirement: Landing page renders a loyalty preview section
The system SHALL render a `#loyalty` section in `LandingView.vue` between the "How It Works" section and the testimonials section. The section SHALL have `bg-clinic-surface` background and SHALL display:
- A heading (e.g., `landing.loyalty.title`) and a one-line introductory sentence about earning points and getting discounts.
- Three horizontally arranged tier cards: **Standard** (0 pts, 0% discount), **Silver** (500 pts, 10% discount), **Gold** (1500 pts, 20% discount). The Gold card SHALL be visually highlighted (e.g., `border-clinic-teal` border and a `text-clinic-teal` accent).
- A "Learn more →" `RouterLink` to `/loyalty` below the cards.

The section data SHALL be hardcoded (no API call).

#### Scenario: Loyalty section is visible on the landing page
- **WHEN** a visitor views the landing page
- **THEN** a loyalty section is rendered showing three tier cards (Standard, Silver, Gold) and a "Learn more" link to `/loyalty`

#### Scenario: Gold tier is visually highlighted
- **WHEN** a visitor views the loyalty tier cards
- **THEN** the Gold card has a distinct visual emphasis compared to Standard and Silver cards

### Requirement: Landing page renders an about-us preview section
The system SHALL render an `#about` section in `LandingView.vue` after the loyalty section (before the contact section). The section SHALL have `bg-white` background and SHALL display:
- A heading (e.g., `landing.about.title`) such as "About Our Clinic".
- Two to three sentences describing the clinic's mission and founding (placeholder Lithuanian text, e.g., `landing.about.body`).
- A "Learn more →" `RouterLink` to `/about`.

No API call is made; the content is i18n-keyed static text.

#### Scenario: About section is visible on the landing page
- **WHEN** a visitor views the landing page
- **THEN** an about-us section is rendered with a heading, descriptive text, and a "Learn more →" link to `/about`

### Requirement: Landing page renders an embedded contact form section
The system SHALL render a `#contact` section in `LandingView.vue` after the about section and before the CTA section. The section SHALL have `bg-clinic-surface` background and SHALL display:
- Static contact info (address, phone, email — matching `ContactView.vue` constants) on the left.
- A contact form on the right with fields: name (required), email (required, email format), subject (required), message (required, textarea), and a "Siųsti" submit button.

On form submit, the component SHALL POST to `POST /api/v1/contact`. On success the form SHALL be replaced with the success message "Jūsų žinutė išsiųsta. Susisieksime netrukus." On server-side validation failure (422), inline field errors SHALL be displayed. The form state SHALL be independent from `ContactView.vue`.

#### Scenario: Contact section is visible on the landing page
- **WHEN** a visitor views the landing page
- **THEN** a contact section is rendered with static contact info and the contact form

#### Scenario: Successful contact form submission on landing page shows success state
- **WHEN** a visitor fills in all required fields and submits the form in the landing page contact section
- **THEN** a POST is sent to `/api/v1/contact` and on success the form is replaced by the success message

#### Scenario: Validation errors displayed inline on landing page contact form
- **WHEN** the server returns a 422 validation error for the landing page contact form
- **THEN** each field with an error shows the error message below it

### Requirement: Landing page sections have anchor IDs for smooth scrolling
The system SHALL add `id` attributes to the following existing sections in `LandingView.vue` so that nav anchor links can scroll to them:
- Hero section: `id="hero"`
- Services section: `id="services"` (already has no id)
- Doctors section: `id="doctors"`
- Testimonials section: `id="testimonials"`

The How-It-Works section already has `id="how-it-works"` and SHALL be preserved.

#### Scenario: Section anchor IDs are present in the DOM
- **WHEN** a visitor loads the landing page
- **THEN** the hero, services, doctors, and testimonials sections each have an `id` attribute matching their slug

### Requirement: Landing page enables smooth scroll globally
The system SHALL ensure the `<html>` element has `scroll-behavior: smooth` so that anchor link navigation on the landing page animates smoothly. This SHALL be set in the global CSS file (`resources/spa/assets/` or equivalent) or via Tailwind's `scroll-smooth` class on a wrapping element.

#### Scenario: Anchor link navigates with smooth animation
- **WHEN** a visitor clicks a nav anchor link on the landing page
- **THEN** the page scrolls to the target section with a smooth animation rather than an instant jump
