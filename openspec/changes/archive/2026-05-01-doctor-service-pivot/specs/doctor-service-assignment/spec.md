## ADDED Requirements

### Requirement: Admin can assign services to a doctor
The system SHALL provide a multi-select field on the `DoctorResource` edit form that allows an admin to assign one or more services to a doctor. The assignment SHALL be stored in the `doctor_service` pivot table. Removing a service from the list SHALL delete the corresponding pivot row.

#### Scenario: Admin assigns services to a doctor
- **WHEN** an admin opens a doctor's edit page and selects services from the multi-select field and saves
- **THEN** the `doctor_service` pivot table contains rows linking that doctor to the selected services

#### Scenario: Admin removes a service from a doctor
- **WHEN** an admin deselects a service from the multi-select field and saves
- **THEN** the corresponding row is removed from the `doctor_service` pivot table

#### Scenario: Doctor with no services assigned
- **WHEN** a doctor has no services selected in the edit form
- **THEN** the `doctor_service` table has no rows for that doctor
