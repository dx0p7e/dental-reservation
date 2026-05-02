## ADDED Requirements

### Requirement: Artisan command generates ScheduleSlot rows from DoctorSchedule records
The `slots:generate` command SHALL read all active `DoctorSchedule` records and create `ScheduleSlot` rows for every matching date in the requested window, using `firstOrCreate` on `[doctor_id, date, start_time]` to remain idempotent.

#### Scenario: Slots are created for active schedules
- **WHEN** `php artisan slots:generate` is run with no options
- **THEN** `ScheduleSlot` rows are created for all active `DoctorSchedule` records for today through today + 14 days, with correct `doctor_id`, `date`, `start_time`, `end_time`, and `slot_type = 'self'`

#### Scenario: Command is idempotent
- **WHEN** `php artisan slots:generate` is run twice for the same date range
- **THEN** no duplicate `ScheduleSlot` rows are created

#### Scenario: Inactive schedules are skipped
- **WHEN** a `DoctorSchedule` has `is_active = false`
- **THEN** no `ScheduleSlot` rows are generated for that schedule

#### Scenario: Custom date range via options
- **WHEN** `php artisan slots:generate --date=2026-06-01 --days=7` is run
- **THEN** slots are generated only for the range 2026-06-01 through 2026-06-07

#### Scenario: Slot times match schedule duration
- **WHEN** a schedule has `start_time=09:00`, `end_time=12:00`, `slot_duration_minutes=30`
- **THEN** six slots are created: 09:00–09:30, 09:30–10:00, 10:00–10:30, 10:30–11:00, 11:00–11:30, 11:30–12:00

### Requirement: Slot generation runs daily via the scheduler
The Laravel scheduler SHALL invoke `slots:generate` (default options: today, 14 days) once per day so the booking calendar stays populated without manual intervention.

#### Scenario: Scheduler entry exists
- **WHEN** `php artisan schedule:list` is inspected
- **THEN** `slots:generate` appears with a daily frequency

### Requirement: Admin can trigger slot generation from the Filament schedule list
`DoctorScheduleResource` SHALL expose a "Generate Slots" row action that lets an admin generate slots for a single schedule on demand, with a configurable `days` field (defaulting to 14).

#### Scenario: Action creates slots for the selected schedule
- **WHEN** an admin clicks "Generate Slots" on a schedule row and confirms
- **THEN** `ScheduleSlot` rows are created for that schedule's doctor, for the next 14 days (or the configured number of days), and a success notification is shown

#### Scenario: Action respects the days input
- **WHEN** an admin enters 30 in the days field and submits
- **THEN** slots are generated for the next 30 days for that schedule
