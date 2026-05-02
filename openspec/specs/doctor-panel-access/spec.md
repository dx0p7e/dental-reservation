### Requirement: Doctor can access the Filament panel at /doctor
The system SHALL provide a dedicated Filament panel at the `/doctor` path. Only users with the Spatie `doctor` role SHALL be able to access this panel. The panel SHALL include its own login page provided by Filament (no Fortify involvement). The panel navigation SHALL show only the Appointments and Schedule resources.

#### Scenario: Doctor with the doctor role logs in at /doctor
- **WHEN** a user with the `doctor` role navigates to `/doctor/login` and submits valid credentials
- **THEN** the system authenticates the user and redirects them to the doctor panel dashboard

#### Scenario: Patient attempts to access /doctor panel
- **WHEN** a user with the `patient` role navigates to `/doctor`
- **THEN** the system redirects them to `/doctor/login` and does not grant access to the panel

#### Scenario: Admin attempts to access /doctor panel
- **WHEN** a user with the `admin` role navigates to `/doctor`
- **THEN** the system redirects them to `/doctor/login` and does not grant access to the panel (admins use `/admin`)

#### Scenario: Unauthenticated user visits /doctor
- **WHEN** an unauthenticated user navigates to `/doctor`
- **THEN** the system redirects them to `/doctor/login`

#### Scenario: Doctor cannot access the admin panel at /admin
- **WHEN** a user with the `doctor` role navigates to `/admin`
- **THEN** the system redirects them to `/admin/login` and does not grant access to the admin panel
