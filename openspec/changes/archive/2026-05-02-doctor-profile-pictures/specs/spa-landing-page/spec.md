## ADDED Requirements

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
