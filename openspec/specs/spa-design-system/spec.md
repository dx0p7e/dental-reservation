## ADDED Requirements

### Requirement: Clinic colour tokens are available as Tailwind utility classes
The system SHALL define seven clinic-specific colour tokens in `resources/css/app.css` inside the `@theme inline {}` block as CSS custom properties following the Tailwind v4 naming convention (`--color-clinic-*`), making them usable as `bg-clinic-dark`, `text-clinic-teal`, etc.

Tokens:
- `--color-clinic-dark: #0F1B2D`
- `--color-clinic-blue: #1A6B8A`
- `--color-clinic-teal: #2ABFBF`
- `--color-clinic-surface: #F8FAFB`
- `--color-clinic-border: #E2E8EE`
- `--color-clinic-text: #0F1B2D`
- `--color-clinic-muted: #5C6B7A`

#### Scenario: Colour tokens are available
- **WHEN** a Vue component uses `bg-clinic-dark` or `text-clinic-teal`
- **THEN** the correct hex colour is applied with no Tailwind build error

### Requirement: Inter font is loaded as the primary sans-serif for the SPA
The system SHALL import the Inter typeface (weights 400, 500, 600, 700) from Google Fonts and declare it as the first font in the `--font-sans` token so that all text inside the SPA shell renders in Inter.

#### Scenario: Inter is rendered on SPA pages
- **WHEN** a patient navigates to any SPA page
- **THEN** headings and body text render in Inter, falling back to `ui-sans-serif` if the font fails to load

### Requirement: AppNavbar component is available for patient-facing pages
The system SHALL provide `resources/spa/components/AppNavbar.vue` — a sticky navigation bar with `bg-clinic-dark` background containing:
- Clinic name / logo on the left in `text-white font-bold`
- Navigation links in the centre: Home (`/`), Book (`/doctors`), My Appointments (`/appointments`), Loyalty (`/loyalty`) — white text, `text-clinic-teal` active underline using `router-link-active`
- A logout button / avatar dropdown on the right when the user is authenticated; "Log in" and "Register" links when unauthenticated

#### Scenario: Authenticated user sees logout option
- **WHEN** the authenticated patient visits any page that includes `AppNavbar`
- **THEN** the navbar shows the user's name with a logout option visible
- **AND** clicking logout calls the auth store logout method and redirects to `/login`

#### Scenario: Unauthenticated visitor sees login and register links
- **WHEN** an unauthenticated visitor views a page with `AppNavbar`
- **THEN** the navbar shows "Log in" and "Register" links instead of the user dropdown

### Requirement: PageHeader component renders a heading with optional breadcrumb
The system SHALL provide `resources/spa/components/PageHeader.vue` accepting a `title` prop (string) and optional `breadcrumbs` prop (array of `{label, to?}` objects), rendering a page-level `<h1>` with `text-clinic-text font-semibold text-2xl tracking-tight` and a breadcrumb trail above it when breadcrumbs are supplied.

#### Scenario: Heading renders without breadcrumbs
- **WHEN** a page uses `<PageHeader title="My Appointments" />`
- **THEN** an `<h1>` with the text "My Appointments" is visible and no breadcrumb trail is rendered

#### Scenario: Heading renders with breadcrumbs
- **WHEN** a page uses `<PageHeader title="Book" :breadcrumbs="[{label:'Home',to:'/'},{label:'Book'}]" />`
- **THEN** a breadcrumb trail "Home > Book" appears above the h1

### Requirement: StatusBadge component maps appointment status to a coloured badge
The system SHALL provide `resources/spa/components/StatusBadge.vue` accepting a `status` prop (string) and rendering a badge with the following colour mapping:
- `pending` → `bg-yellow-100 text-yellow-800`
- `confirmed` → `bg-blue-100 text-blue-800`
- `completed` → `bg-green-100 text-green-800`
- `cancelled` → `bg-red-100 text-red-800`
- `no_show` → `bg-gray-100 text-gray-600`

#### Scenario: Correct colour class applied for each status
- **WHEN** `<StatusBadge status="confirmed" />` is rendered
- **THEN** the badge has blue background and text classes and displays the capitalised label "Confirmed"

#### Scenario: Unknown status falls back gracefully
- **WHEN** `<StatusBadge status="unknown_value" />` is rendered
- **THEN** the badge renders with a neutral grey style and displays the raw status value
