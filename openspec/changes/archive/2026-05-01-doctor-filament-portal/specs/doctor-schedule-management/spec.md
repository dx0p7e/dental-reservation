## ADDED Requirements

### Requirement: Doctor can view their own schedule rows only
The system SHALL restrict the doctor's schedule list to rows where `doctor_schedules.doctor_id` matches the authenticated doctor's `Doctor.id`. Schedules belonging to other doctors SHALL NOT appear.

#### Scenario: Doctor views their schedule list
- **WHEN** a doctor navigates to the Schedule resource in the doctor panel
- **THEN** only schedule rows with a `doctor_id` matching their own doctor profile are shown

#### Scenario: Doctor cannot see another doctor's schedule rows
- **WHEN** the schedule list is rendered for doctor A
- **THEN** no schedule rows belonging to doctor B are present in the list

### Requirement: Doctor can create a new schedule row
The system SHALL allow a doctor to create a new schedule row. The `doctor_id` SHALL always be forced to the authenticated doctor's ID and SHALL NOT be editable in the form. The form SHALL expose: day of week, start time, end time, slot duration (minutes), and active toggle.

#### Scenario: Doctor creates a valid schedule row
- **WHEN** a doctor submits the create schedule form with valid day, start time, end time, slot duration, and active status
- **THEN** a new `DoctorSchedule` record is created with `doctor_id` set to the authenticated doctor's `Doctor.id`

#### Scenario: doctor_id is always the authenticated doctor
- **WHEN** a doctor creates or edits a schedule row
- **THEN** the `doctor_id` on the saved record always equals the authenticated doctor's `Doctor.id` regardless of any form input

### Requirement: Doctor can edit an existing schedule row
The system SHALL allow a doctor to edit their own schedule rows. The `doctor_id` SHALL remain unchanged after editing. The doctor SHALL NOT be able to edit schedule rows belonging to another doctor.

#### Scenario: Doctor edits their own schedule row
- **WHEN** a doctor opens one of their own schedule rows and saves changes
- **THEN** the schedule row is updated and `doctor_id` remains unchanged

#### Scenario: Doctor cannot edit another doctor's schedule row
- **WHEN** a doctor attempts to navigate to the edit page of a schedule row belonging to another doctor
- **THEN** the system returns a 403 or 404 response

### Requirement: Doctor can delete their own schedule row
The system SHALL allow a doctor to delete their own schedule rows. The doctor SHALL NOT be able to delete schedule rows belonging to another doctor.

#### Scenario: Doctor deletes their own schedule row
- **WHEN** a doctor triggers the delete action on one of their own schedule rows
- **THEN** the schedule row is removed from the database

#### Scenario: Doctor cannot delete another doctor's schedule row
- **WHEN** a doctor attempts to delete a schedule row belonging to another doctor
- **THEN** the system returns a 403 or 404 response and the row is not deleted
