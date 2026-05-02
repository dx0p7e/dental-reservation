## 1. ServiceSeeder

- [x] 1.1 Create `database/seeders/ServiceSeeder.php` with all 8 dental services using `firstOrCreate` keyed on `name`
- [x] 1.2 Seed a `LoyaltyRule` for each service (`points_earned` per euro, `discount_pct = 10`) using `updateOrCreate` keyed on `service_id`

## 2. DoctorSeeder

- [x] 2.1 Create `database/seeders/DoctorSeeder.php` — create 3 `User` records (role `doctor`, verified email, hashed password `password`) using `firstOrCreate` keyed on `email`
- [x] 2.2 Create a linked `Doctor` record for each user with name, specialization, and bio
- [x] 2.3 Assign each doctor to their designated services via `doctor_service` pivot using `syncWithoutDetaching`

## 3. PatientSeeder

- [x] 3.1 Create `database/seeders/PatientSeeder.php` — create 4 `User` records (role `patient`) using `firstOrCreate` keyed on `email`
- [x] 3.2 Set `email_verified_at`, `gdpr_consented_at`, and a Lithuanian phone number on each patient

## 4. DoctorScheduleSeeder

- [x] 4.1 Create `database/seeders/DoctorScheduleSeeder.php` — for each of the 3 doctors, create 5 `DoctorSchedule` rows (Mon–Fri, `day_of_week` 0–4) using `firstOrCreate` keyed on `(doctor_id, day_of_week)`
- [x] 4.2 Set `start_time = '09:00'`, `end_time = '17:00'`, `slot_duration_minutes = 30`, `is_active = true` on each row

## 5. SlotSeeder

- [x] 5.1 Create `database/seeders/SlotSeeder.php` — loop over **last 30 days + next 14 days**, skip weekends
- [x] 5.2 For each weekday in that range, create 30-minute `ScheduleSlot` blocks from 09:00–17:00 for each of the 3 doctors using `firstOrCreate` keyed on `(doctor_id, date, start_time)`; set `slot_type = 'self'` on every row

## 6. AppointmentSeeder

- [x] 6.1 Create `database/seeders/AppointmentSeeder.php` — seed 2 past `completed` appointments per patient, each linked to a past slot dated **within the current calendar month** (so the admin dashboard "this month" filter shows non-zero counts by default)
- [x] 6.2 Seed 1 future `confirmed` appointment per patient linked to a future slot
- [x] 6.3 Seed 1 past `no_show` appointment for one patient
- [x] 6.4 Seed 1–2 `pending` appointments across patients
- [x] 6.5 Mark all slots linked to `completed` or `confirmed` appointments as `is_booked = true`

## 7. LoyaltySeeder

- [x] 7.1 Create `database/seeders/LoyaltySeeder.php` — create a `LoyaltyAccount` per patient using `firstOrCreate` keyed on `patient_id`
- [x] 7.2 For each past `completed` appointment, create a `LoyaltyTransaction` (`type = earn`, `points_delta` from service's `LoyaltyRule`) if not already present
- [x] 7.3 Set `points_balance` and `tier` explicitly on each account per the spec table

## 8. DemoSeeder Orchestrator

- [x] 8.1 Create `database/seeders/DemoSeeder.php` — call sub-seeders in dependency order: `RoleSeeder`, `LoyaltyTierSeeder`, `ServiceSeeder`, `DoctorSeeder`, `PatientSeeder`, `DoctorScheduleSeeder`, `SlotSeeder`, `AppointmentSeeder`, `LoyaltySeeder` (includes prerequisites so DemoSeeder is self-sufficient when called standalone)
- [x] 8.2 Include admin user seeding in `DemoSeeder` (delegate to `AdminUserSeeder` or inline `firstOrCreate` for `admin@klinika.lt`)
- [x] 8.3 Register `DemoSeeder` in `DatabaseSeeder::run()` so it is available via `php artisan db:seed --class=DemoSeeder`

## 9. Verification

- [x] 9.1 Run `php artisan db:seed --class=DemoSeeder` on a freshly migrated database and confirm no errors
- [x] 9.2 Re-run to verify idempotency — no duplicates created
- [ ] 9.3 Log in as each patient and confirm appointment history and loyalty tier are correct
- [ ] 9.4 Log in as each doctor and confirm `My Schedule` shows 5 weekday rows and appointments list is populated
- [ ] 9.5 Log in as admin and confirm all records visible in the admin panel and dashboard widgets show non-zero counts
