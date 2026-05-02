## ADDED Requirements

### Requirement: Landing page renders a sticky dark navigation bar
The system SHALL render a sticky top navigation bar on `LandingView.vue` with `bg-clinic-dark` background containing the clinic name on the left (white, bold), anchor links to page sections (Home, Services, About, Contact) in the centre, and a "Book Appointment" button (`bg-clinic-teal text-white rounded-lg px-5 py-2`) on the right.

#### Scenario: Navbar is visible at page top
- **WHEN** a visitor loads the landing page at `/`
- **THEN** the sticky navbar is visible at the top with the clinic name and "Book Appointment" button

#### Scenario: Book Appointment button navigates to booking
- **WHEN** a visitor clicks "Book Appointment" in the navbar
- **THEN** the router navigates to `/doctors` (booking entry)

### Requirement: Landing page renders a full-viewport hero section
The system SHALL render a hero section with `bg-clinic-dark` background that fills the viewport height, displaying:
- Primary headline: "Modern Dental Care, On Your Schedule" — `text-white text-5xl font-semibold tracking-tight`
- Subtext paragraph — `text-white/70 text-lg leading-relaxed`
- Two CTA buttons: "Book Now" (`bg-clinic-teal text-white rounded-lg`) and "Learn More" (outlined, `border border-white text-white rounded-lg`), displayed side by side

#### Scenario: Hero CTAs are visible
- **WHEN** a visitor views the landing page
- **THEN** both "Book Now" and "Learn More" buttons are visible in the hero

#### Scenario: Book Now navigates to booking flow
- **WHEN** the visitor clicks "Book Now" in the hero
- **THEN** the router navigates to `/doctors`

### Requirement: Landing page renders a services section with a three-column card grid
The system SHALL render a services section (`bg-white`) with a "Our Services" heading and a three-column card grid. The service data SHALL be fetched from `GET /api/v1/services` on mount; there is no hardcoded array. Each card SHALL have `border border-clinic-border rounded-lg p-6` and contain: service name in `font-semibold`, duration and price in `text-clinic-muted` (formatted as `{duration_minutes} min · €{price}`), and a "Book →" `RouterLink` in `text-clinic-blue`. Cards SHALL NOT display a Heroicon or any icon (no icon field is present in the API response). If the API returns more than 6 services, only the first 6 SHALL be rendered and a "View all services →" `RouterLink` to `/services` SHALL appear below the grid. If the API call fails, the section SHALL degrade silently to an empty services array (no error banner). While the API call is in-flight, 3 animated skeleton placeholder cards (`animate-pulse`) SHALL be shown.

#### Scenario: Services section shows live API cards
- **WHEN** a visitor views the landing page and `GET /api/v1/services` returns services
- **THEN** the services section displays one card per service (up to 6), each with name, duration, price, and "Book →" link — no icon

#### Scenario: View all link appears when more than 6 services exist
- **WHEN** `GET /api/v1/services` returns more than 6 services
- **THEN** only the first 6 cards are rendered and a "View all services →" link pointing to `/services` is visible below the grid

#### Scenario: Silent empty state on API failure
- **WHEN** `GET /api/v1/services` fails
- **THEN** the services section renders with no cards and no error message

#### Scenario: Skeleton cards shown during loading
- **WHEN** the landing page is loading and the services API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible in the services grid area

### Requirement: Landing page renders a stats row (Why Us section)
The system SHALL render a "Why Us" section (`bg-clinic-surface`) with four stat cards displayed in a row. Each card SHALL display a large statistic or icon in `text-clinic-teal` and a label below in `text-clinic-muted`. Content: "10+ Years Experience", "2000+ Patients", "Modern Equipment", "Painless Procedures".

#### Scenario: All four stat cards are visible
- **WHEN** a visitor views the landing page
- **THEN** four stat cards are visible in a single row with teal-coloured numbers/icons

### Requirement: Landing page renders a How It Works section
The system SHALL render a "How It Works" section (`bg-white`) with a three-step horizontal flow. Each step SHALL have a numbered circle with `bg-clinic-teal text-white` and a label below. Steps: "Create Account" (1), "Choose a Service & Slot" (2), "Get Confirmed" (3). A horizontal line connector SHALL visually link the three steps.

#### Scenario: Three steps with connectors are rendered
- **WHEN** a visitor views the landing page
- **THEN** three numbered steps are displayed horizontally with a connecting line between them

### Requirement: Landing page renders a testimonials section
The system SHALL render a reviews/testimonials section (`bg-clinic-surface`) on `LandingView.vue`. Review data SHALL be fetched from `GET /api/v1/reviews` on mount. The section SHALL display the 3 most recent reviews. Each review card SHALL display: star rating in `text-clinic-teal`, the review `body` text (truncated with `line-clamp-3`), and the `patient_name` (first name + last initial). While the API call is in-flight, 3 animated skeleton placeholder cards SHALL be shown. If the API returns no reviews, the section SHALL be hidden. A "Peržiūrėti visus atsiliepimus →" `RouterLink` to `/reviews` SHALL appear below the cards.

#### Scenario: Three most recent reviews are displayed
- **WHEN** a visitor views the landing page and `GET /api/v1/reviews` returns reviews
- **THEN** the three most recent review cards are visible with star rating, truncated body, and patient name

#### Scenario: Section hidden when no reviews
- **WHEN** `GET /api/v1/reviews` returns an empty array
- **THEN** the reviews section is not rendered on the landing page

#### Scenario: Skeleton shown during loading
- **WHEN** the landing page is loading and the reviews API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible in the reviews section

#### Scenario: View all link visible
- **WHEN** at least one review is displayed
- **THEN** a "Peržiūrėti visus atsiliepimus →" link pointing to `/reviews` is visible below the cards

### Requirement: Landing page renders a CTA section and a footer
The system SHALL render a call-to-action section (`bg-clinic-dark`) with the headline "Ready for your next appointment?" in `text-white` and a single "Book Now" `bg-clinic-teal` button. Below it, a footer (`bg-clinic-dark text-white`) SHALL contain three columns (clinic name + tagline, navigation links, contact details) and a bottom copyright bar.

#### Scenario: CTA and footer are present at the bottom
- **WHEN** a visitor scrolls to the bottom of the landing page
- **THEN** the CTA section with "Book Now" and the three-column footer with copyright text are visible

### Requirement: Landing page renders a doctors section with photo avatars
The system SHALL render a "Meet Our Doctors" section on `LandingView.vue`. Doctor data SHALL be fetched from `GET /api/v1/doctors` on mount. Each doctor card SHALL display:
- A circular avatar (photo or initials fallback — same rules as `DoctorsView`) sized `w-16 h-16` at the top of the card, centred
- The doctor's name in `font-semibold text-clinic-text`
- The doctor's specialisation in `text-sm text-clinic-blue`
- A truncated bio (`line-clamp-2 text-sm text-clinic-muted`)
- A "Book" button that navigates to `GET /doctors/{id}/slots`

While the API call is in-flight, 3 animated skeleton placeholder cards SHALL be shown. If the API returns no doctors, the section SHALL be hidden. A "View all doctors →" `RouterLink` to `/doctors` SHALL appear below the grid.

#### Scenario: Doctors section shows cards with avatars
- **WHEN** a visitor views the landing page and `GET /api/v1/doctors` returns doctors
- **THEN** each doctor card displays a circular avatar (photo if available, initials otherwise), name, specialisation, and truncated bio

#### Scenario: Photo avatar displayed when available
- **WHEN** a doctor has a non-null `photo_url`
- **THEN** the landing page doctor card shows a circular photo image

#### Scenario: Initials avatar displayed when no photo
- **WHEN** a doctor has `photo_url = null`
- **THEN** the landing page doctor card shows a teal circular div with the doctor's initials

#### Scenario: Section hidden when no doctors
- **WHEN** `GET /api/v1/doctors` returns an empty array
- **THEN** the doctors section is not rendered on the landing page

#### Scenario: Skeleton cards shown during loading
- **WHEN** the landing page is loading and the doctors API has not yet responded
- **THEN** 3 pulsing skeleton placeholder cards are visible in the doctors section

### Requirement: Landing page renders a loyalty preview section
The system SHALL render a `#loyalty` section in `LandingView.vue` between the "How It Works" section and the doctors section. The section SHALL have `bg-clinic-surface` background and SHALL display:
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
The system SHALL render an `#about` section in `LandingView.vue` after the loyalty section. The section SHALL have `bg-white` background and SHALL display:
- A heading (e.g., `landing.about.title`) such as "About Our Clinic".
- Two to three sentences describing the clinic's mission and founding (placeholder Lithuanian text, e.g., `landing.about.body`).
- A "Learn more →" `RouterLink` to `/about`.

No API call is made; the content is i18n-keyed static text.

#### Scenario: About section is visible on the landing page
- **WHEN** a visitor views the landing page
- **THEN** an about-us section is rendered with a heading, descriptive text, and a "Learn more →" link to `/about`

### Requirement: Landing page renders a doctors preview section between About and Contact
The system SHALL render the `#doctors` section after the About section with `bg-clinic-surface` background (alternating from the white About background). This placement reflects the section order: Services → Loyalty → About → Doctors → Contact.

### Requirement: Landing page renders an embedded contact form section
The system SHALL render a `#contact` section in `LandingView.vue` after the doctors section and before the testimonials section. The section SHALL have `bg-white` background and SHALL display:
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
The system SHALL add `id` attributes to the following sections in `LandingView.vue`:
- Hero section: `id="hero"`
- Services section: `id="services"`
- Loyalty section: `id="loyalty"`
- About section: `id="about"`
- Doctors section: `id="doctors"`
- Contact section: `id="contact"`
- Testimonials section: `id="testimonials"`

The root `<div>` SHALL use `scroll-smooth` to enable CSS smooth scrolling for anchor links.
