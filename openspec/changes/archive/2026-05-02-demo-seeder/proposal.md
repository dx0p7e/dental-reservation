## Why

The application currently has no structured seed data — only ad-hoc manual entries created during development. For the thesis defence, the database must look like a real operating dental clinic, with named doctors, real services, patient appointment history, and active loyalty accounts that demonstrate the full system in action.

## What Changes

- New `DemoSeeder.php` orchestrator registered in `DatabaseSeeder.php` — runs the full set via `php artisan db:seed --class=DemoSeeder`
- New `ServiceSeeder.php` — 8 real dental services with prices, durations, and descriptions; loyalty earning rules seeded per service
- New `DoctorSeeder.php` — 3 named doctors with linked user accounts (role `doctor`), specializations, bios, and doctor–service pivot assignments
- New `PatientSeeder.php` — 4 named patients with verified emails, Lithuanian phone numbers, and GDPR consent timestamps
- New `SlotSeeder.php` — schedule slots seeded for the next 14 days (Mon–Fri, 09:00–17:00) per doctor, block sizes matching service durations
- New `AppointmentSeeder.php` — 2–4 appointments per patient spanning past (`completed`, `no_show`) and future (`confirmed`, `pending`) statuses; marks corresponding slots as booked
- New `LoyaltySeeder.php` — loyalty accounts and point transaction history derived from completed appointments via `LoyaltyService::recordEarn()`
- Admin user seeded idempotently if not present (`admin@klinika.lt`)
- All seeders use `firstOrCreate` / `updateOrCreate` — re-running is safe

## Capabilities

### New Capabilities

- `demo-seeder`: Realistic dental clinic demo dataset — services, doctors, patients, appointments, loyalty accounts — seeded in dependency order via a single orchestrator class

### Modified Capabilities

- None

## Impact

- `database/seeders/` — 7 new files created
- `database/seeders/DatabaseSeeder.php` — `DemoSeeder::class` call added
- No migration changes
- No factory changes (seeders use explicit data, not factories)
- No API or frontend changes
- Depends on: `LoyaltyService`, `Doctor`, `User`, `Service`, `DoctorService`, `Slot`, `Appointment`, `LoyaltyAccount`, `LoyaltyTransaction` models all existing
