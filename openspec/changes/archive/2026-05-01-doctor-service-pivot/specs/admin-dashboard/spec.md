## ADDED Requirements

### Requirement: Admin can assign services to doctors from the DoctorResource edit form
The system SHALL add a multi-select services field to the `DoctorResource` edit form in Filament. The field SHALL use `Select::make('services')->multiple()->relationship('services', 'name')` (or equivalent). Changes SHALL be persisted to the `doctor_service` pivot on save.

#### Scenario: Admin opens doctor edit form
- **WHEN** an admin opens a doctor edit page in the Filament admin panel
- **THEN** a "Services" multi-select field is visible showing all available services

#### Scenario: Admin saves service assignments
- **WHEN** an admin selects services and saves the form
- **THEN** the `doctor_service` pivot is updated to reflect the selected services
