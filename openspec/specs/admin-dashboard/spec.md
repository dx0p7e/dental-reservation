## Requirements

### Requirement: Admin dashboard has a date-range filter

The system SHALL provide a period selector on the admin dashboard (This Month / Last Month / Last 30 Days / All Time). All analytics widgets SHALL respond to the selected period.

#### Scenario: Default period on page load

- **WHEN** an admin opens the dashboard with no prior filter state
- **THEN** the widgets display data for the current calendar month

#### Scenario: Admin changes period to Last Month

- **WHEN** an admin selects "Last Month" from the period filter
- **THEN** all three widgets re-render with data scoped to the previous calendar month

#### Scenario: Admin selects All Time

- **WHEN** an admin selects "All Time"
- **THEN** all three widgets display totals with no date restriction

### Requirement: Appointments overview widget shows status breakdown

The system SHALL display a stats overview widget with: total appointments, count of Pending, count of Confirmed, count of Completed, count of Cancelled — all scoped to the selected period.

#### Scenario: Widget shows totals for current month

- **WHEN** the dashboard period is "This Month"
- **THEN** the appointments widget displays counts matching appointments occurring within the current calendar month

#### Scenario: Widget reflects zero counts correctly

- **WHEN** there are no appointments in the selected period
- **THEN** the widget shows 0 for all stat cards

### Requirement: Loyalty distribution widget shows patient count per tier

The system SHALL display a stats overview widget with the count of loyalty accounts at each tier (Standard, Silver, Gold). This count is NOT filtered by date — it reflects the current distribution.

#### Scenario: Widget shows current tier counts

- **WHEN** an admin views the dashboard
- **THEN** the loyalty widget shows how many patients are at each tier

### Requirement: Revenue estimate widget shows completed appointment value

The system SHALL display a stats widget showing the sum of `services.price` for all appointments with status `Completed` in the selected period. The widget SHALL be clearly labelled as an estimate.

#### Scenario: Widget shows revenue for completed appointments

- **WHEN** the selected period has completed appointments
- **THEN** the widget shows the total list-price value of those appointments

#### Scenario: Widget shows zero when no completed appointments

- **WHEN** there are no completed appointments in the selected period
- **THEN** the widget displays "£0.00" (or equivalent zero currency value)

### Requirement: Admin can assign services to doctors from the DoctorResource edit form
The system SHALL add a multi-select services field to the `DoctorResource` edit form in Filament. The field SHALL use `Select::make('services')->multiple()->relationship('services', 'name')` (or equivalent). Changes SHALL be persisted to the `doctor_service` pivot on save.

#### Scenario: Admin opens doctor edit form
- **WHEN** an admin opens a doctor edit page in the Filament admin panel
- **THEN** a "Services" multi-select field is visible showing all available services

#### Scenario: Admin saves service assignments
- **WHEN** an admin selects services and saves the form
- **THEN** the `doctor_service` pivot is updated to reflect the selected services
