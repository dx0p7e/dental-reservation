## ADDED Requirements

### Requirement: DemoSeeder orchestrates all sub-seeders in dependency order
A `DemoSeeder` class SHALL exist in `database/seeders/DemoSeeder.php` and SHALL call all demo sub-seeders in the correct dependency order: `ServiceSeeder`, `DoctorSeeder`, `PatientSeeder`, `DoctorScheduleSeeder`, `SlotSeeder`, `AppointmentSeeder`, `LoyaltySeeder`. It SHALL be callable via `php artisan db:seed --class=DemoSeeder`.

#### Scenario: Running DemoSeeder on a clean database
- **WHEN** `php artisan db:seed --class=DemoSeeder` is run on an empty database (after migrations)
- **THEN** all 8 sub-seeders run in order without error
- **AND** the database contains 8 services, 3 doctors, 4 patients, doctor schedule rows, slots for the last 30 days and next 14 days, appointments, and loyalty accounts

#### Scenario: Re-running DemoSeeder is idempotent
- **WHEN** `php artisan db:seed --class=DemoSeeder` is run a second time on an already-seeded database
- **THEN** no duplicate records are created
- **AND** the command exits successfully

### Requirement: ServiceSeeder seeds 8 dental services with loyalty rules
`ServiceSeeder` SHALL create exactly 8 `Service` records using `firstOrCreate` keyed on `name`. For each service a `LoyaltyRule` record SHALL be created with `points_earned` = 1 per euro (rounded), `discount_pct` = 10, and no expiry.

The 8 services SHALL be:

| Name | Duration (min) | Price |
|---|---|---|
| Teeth Cleaning (Hygiene) | 60 | 45.00 |
| Dental Examination & X-Ray | 30 | 35.00 |
| Tooth Filling (Composite) | 45 | 80.00 |
| Tooth Extraction | 30 | 60.00 |
| Root Canal Treatment | 90 | 180.00 |
| Teeth Whitening | 60 | 120.00 |
| Dental Crown | 60 | 250.00 |
| Children's Dental Check | 30 | 25.00 |

#### Scenario: Services are present after seeding
- **WHEN** `ServiceSeeder` runs
- **THEN** 8 `Service` records exist with the specified names, durations, and prices
- **AND** each service has a corresponding `LoyaltyRule` with `discount_pct = 10`

### Requirement: DoctorSeeder seeds 3 doctors with user accounts and service assignments
`DoctorSeeder` SHALL create 3 `User` records (role `doctor`, `email_verified_at` set, password `password`) and a linked `Doctor` record for each. It SHALL also assign each doctor to their designated services via the `doctor_service` pivot.

| Doctor | Email | Specialization | Services |
|---|---|---|---|
| Dr. Marta Kazlauskienė | marta.kazlauskiene@klinika.lt | General Dentistry | Teeth Cleaning, Dental Examination, Tooth Filling, Tooth Extraction, Children's Dental Check |
| Dr. Tomas Petrauskas | tomas.petrauskas@klinika.lt | Orthodontics & Endodontics | Dental Examination, Root Canal Treatment, Tooth Extraction, Tooth Filling |
| Dr. Aistė Rimkutė | aiste.rimkute@klinika.lt | Cosmetic Dentistry | Teeth Whitening, Dental Crown, Teeth Cleaning, Dental Examination |

#### Scenario: Doctor accounts are present after seeding
- **WHEN** `DoctorSeeder` runs after `ServiceSeeder`
- **THEN** 3 `Doctor` records exist with the specified names and specializations
- **AND** each doctor has a linked `User` with role `doctor` and verified email
- **AND** the `doctor_service` pivot contains the correct service assignments

### Requirement: PatientSeeder seeds 4 patients with verified accounts
`PatientSeeder` SHALL create 4 `User` records (role `patient`, `email_verified_at` set, `gdpr_consented_at` set, password `password`) with Lithuanian phone numbers.

| Name | Email |
|---|---|
| Jonas Stankevičius | jonas.s@example.lt |
| Eglė Mackevičiūtė | egle.m@example.lt |
| Rūta Jankauskaite | ruta.j@example.lt |
| Andrius Butkus | andrius.b@example.lt |

#### Scenario: Patient accounts are present after seeding
- **WHEN** `PatientSeeder` runs
- **THEN** 4 `User` records with role `patient` exist with the specified emails
- **AND** each has `email_verified_at` and `gdpr_consented_at` set

### Requirement: DoctorScheduleSeeder seeds working-hours schedule per doctor
`DoctorScheduleSeeder` SHALL create 5 `DoctorSchedule` rows per doctor (Monday–Friday, `day_of_week` 0–4) using `firstOrCreate` keyed on `(doctor_id, day_of_week)`. Each row SHALL have `start_time = '09:00'`, `end_time = '17:00'`, `slot_duration_minutes = 30`, and `is_active = true`.

#### Scenario: Doctor schedules are present after seeding
- **WHEN** `DoctorScheduleSeeder` runs after `DoctorSeeder`
- **THEN** each of the 3 doctors has 5 `DoctorSchedule` rows (Mon–Fri)
- **AND** the doctor portal "My Schedule" panel shows all 5 rows for each doctor

### Requirement: SlotSeeder seeds schedule slots covering past and future weekdays per doctor
`SlotSeeder` SHALL create `ScheduleSlot` records for each of the 3 doctors covering the last 30 calendar days and the next 14 calendar days, Mon–Fri only, from 09:00 to 17:00 in 30-minute blocks. Slots SHALL use `firstOrCreate` keyed on `(doctor_id, date, start_time)` and SHALL have `slot_type = 'self'`.

#### Scenario: Slots are present after seeding
- **WHEN** `SlotSeeder` runs after `DoctorSeeder`
- **THEN** each doctor has slots for every weekday in the last 30 days and next 14 days between 09:00 and 17:00

### Requirement: AppointmentSeeder seeds realistic appointment history per patient
`AppointmentSeeder` SHALL create 2–4 appointments per patient across past and future slots. Past `completed` appointments SHALL be linked to slots dated **within the current calendar month** so that the admin dashboard "this month" filter shows non-zero revenue and appointment counts by default. Past `completed` appointments SHALL mark their slot as `is_booked = true`. Future `confirmed` appointments SHALL also mark their slot as `is_booked = true`.

Distribution per patient:
- 2 past `completed` appointments
- 1 future `confirmed` appointment
- At least 1 patient SHALL have 1 past `no_show` appointment
- 1–2 `pending` appointments across all patients

#### Scenario: Appointment history is present after seeding
- **WHEN** `AppointmentSeeder` runs after `PatientSeeder`, `DoctorSeeder`, and `SlotSeeder`
- **THEN** each patient has at least 2 past completed appointments and 1 future confirmed appointment
- **AND** all slots linked to completed or confirmed appointments have `is_booked = true`

### Requirement: LoyaltySeeder seeds loyalty accounts and point transactions
`LoyaltySeeder` SHALL create a `LoyaltyAccount` for each patient using `firstOrCreate`. For each past `completed` appointment, it SHALL create a `LoyaltyTransaction` with `type = earn` and `points_delta` derived from the service's `LoyaltyRule`. The `points_balance` and `tier` on the account SHALL be set to the values below (tier is set explicitly, not derived from threshold).

| Patient | points_balance | tier |
|---|---|---|
| Jonas Stankevičius | 125 | silver |
| Eglė Mackevičiūtė | 45 | standard |
| Rūta Jankauskaite | 210 | gold |
| Andrius Butkus | 15 | standard |

#### Scenario: Loyalty accounts are present after seeding
- **WHEN** `LoyaltySeeder` runs after `AppointmentSeeder`
- **THEN** each of the 4 patients has a `LoyaltyAccount` with the specified `points_balance` and `tier`
- **AND** each completed appointment has a corresponding `earn` transaction on the account

### Requirement: Admin user is seeded idempotently
`DemoSeeder` (or a delegated sub-seeder) SHALL ensure an admin user exists with email `admin@klinika.lt`, password `password`, and role `admin`.

#### Scenario: Admin user is present after seeding
- **WHEN** `DemoSeeder` runs
- **THEN** a user with email `admin@klinika.lt` and role `admin` exists in the database
