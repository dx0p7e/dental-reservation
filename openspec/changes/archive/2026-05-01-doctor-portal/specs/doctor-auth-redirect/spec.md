## ADDED Requirements

### Requirement: Doctor is redirected to their portal after login
After a successful web session login, the system SHALL redirect authenticated users to a role-specific destination: doctors to `/doctor/dashboard`, admins to `/filament`, and all other users to `/dashboard`.

#### Scenario: Doctor login redirects to doctor dashboard
- **WHEN** a user with the Spatie `doctor` role completes a successful login via Fortify
- **THEN** they are redirected to `/doctor/dashboard`

#### Scenario: Patient login is unaffected
- **WHEN** a user with the Spatie `patient` role completes a successful login
- **THEN** they are redirected to `/dashboard`

#### Scenario: Admin login is unaffected
- **WHEN** a user with the Spatie `admin` role completes a successful login
- **THEN** they are redirected to `/filament`
