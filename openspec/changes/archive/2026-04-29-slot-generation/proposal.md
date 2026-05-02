## Why

`DoctorSchedule` defines when a doctor works, but nothing ever materialises the actual `ScheduleSlot` rows that patients book. As a result, `SlotController@index` always returns an empty collection and no appointments can be made. Slot generation must be automated so the calendar stays populated without manual intervention.

## What Changes

- New Artisan command `slots:generate` that reads active `DoctorSchedule` records and creates `ScheduleSlot` rows for a requested date range, using `firstOrCreate` to skip already-generated slots.
- New scheduled task in `routes/console.php` that runs `slots:generate` daily, generating 14 days ahead.
- New Filament action on `DoctorScheduleResource` — a "Generate Slots" table row action so admins can trigger generation for a specific doctor's schedule on demand.

## Capabilities

### New Capabilities
- `slot-generation`: Artisan command, daily schedule, and Filament action that generate `ScheduleSlot` rows from `DoctorSchedule` records for a given date range.

### Modified Capabilities
<!-- No existing spec-level requirements change -->

## Impact

- `app/Console/Commands/GenerateSlotsCommand.php` — new command
- `routes/console.php` — daily schedule entry
- `app/Filament/Resources/DoctorSchedules/Tables/DoctorSchedulesTable.php` — new table action
- `database/migrations/` — no schema changes; uses existing `schedule_slots` + `doctor_schedules` tables
- `app/Models/DoctorSchedule.php` — `slots()` relationship added if not present
