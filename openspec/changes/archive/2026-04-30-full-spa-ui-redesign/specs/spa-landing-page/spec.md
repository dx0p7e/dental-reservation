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
The system SHALL render a services section (`bg-white`) with a "Our Services" heading and a three-column card grid. Each card SHALL have `border border-clinic-border rounded-lg p-6` and contain: a Heroicon (outline, 24px) at the top, service name in `font-semibold`, duration and price in `text-clinic-muted`, and a "Book" link in `text-clinic-blue`. Service data SHALL be hardcoded with at least three representative dental services.

#### Scenario: Services section shows three cards
- **WHEN** a visitor views the landing page
- **THEN** the services section displays exactly three service cards, each with a name, duration, price, and "Book" link

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
The system SHALL render a testimonials section (`bg-clinic-surface`) with three quote cards (`border rounded-lg p-6`). Each card SHALL display: a quoted text excerpt, the patient's first name and last initial, and a star rating displayed in `text-clinic-teal`. Content may be hardcoded representative testimonials.

#### Scenario: Three testimonial cards are visible
- **WHEN** a visitor views the landing page
- **THEN** three testimonial cards are visible with quote text, patient name, and star rating

### Requirement: Landing page renders a CTA section and a footer
The system SHALL render a call-to-action section (`bg-clinic-dark`) with the headline "Ready for your next appointment?" in `text-white` and a single "Book Now" `bg-clinic-teal` button. Below it, a footer (`bg-clinic-dark text-white`) SHALL contain three columns (clinic name + tagline, navigation links, contact details) and a bottom copyright bar.

#### Scenario: CTA and footer are present at the bottom
- **WHEN** a visitor scrolls to the bottom of the landing page
- **THEN** the CTA section with "Book Now" and the three-column footer with copyright text are visible
