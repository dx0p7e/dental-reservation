## Context

`DoctorSchedule` rows define a repeating weekly template: doctor, `day_of_week` (1=Monday … 7=Sunday, ISO), `start_time`, `end_time`, `slot_duration_minutes`, `is_active`. The existing `schedule_slots` table stores concrete, bookable date+time rows linked to a doctor. Nothing currently bridges the two — no code ever reads a schedule and writes slot rows. `SlotController@index` therefore always returns `[]`.

## Goals / Non-Goals

**Goals:**
- Artisan command `slots:generate` that iterates active `DoctorSchedule` records and produces `ScheduleSlot` rows for a given date window using `firstOrCreate` (idempotent).
- Daily scheduled invocation via Laravel's scheduler, generating 14 days ahead.
- A Filament table row action on `DoctorScheduleResource` that triggers generation for a single schedule, so admins can populate slots immediately after creating/editing a schedule.

**Non-Goals:**
- Deleting or invalidating previously-generated slots when a schedule changes.
- Generating slots retroactively for past dates.
- Any changes to the `schedule_slots` or `doctor_schedules` schema.

## Decisions

### `slot_type` value
The `schedule_slots.slot_type` enum has two values: `self` and `request`. Generated slots from a `DoctorSchedule` are admin-defined availability windows, so they use `self`.

*Alternative:* a configurable per-schedule type was considered but adds complexity with no current use case.

### Day-of-week convention
The `doctor_schedules` table stores ISO weekday values: **1=Monday … 7=Sunday**. This is what `DoctorScheduleForm` writes (options keyed 1–7) and what `DoctorScheduleFactory` generates (`numberBetween(1, 7)`). The original migration comment stating "0=Monday" is incorrect and should be ignored.

When iterating a date range, use `$carbon->isoWeekday()` (returns 1–7) to match the stored value directly. Do **not** use `($carbon->dayOfWeek + 6) % 7` — that formula produces 0–6 and will never match any DB row.

### `firstOrCreate` uniqueness key
Uniqueness for a slot is `[doctor_id, date, start_time]`. End time is derived, so this triple is sufficient to prevent duplicates on re-runs.

### Command signature
`php artisan slots:generate {--date=} {--days=14}` — `--date` defaults to today, `--days` defaults to 14. The daily schedule always runs with defaults. Admins or CI can pass explicit values.

### SlotGenerationService
Slot generation logic SHALL live in `app/Services/SlotGenerationService.php` with a single public method:

```
generateForSchedule(DoctorSchedule $schedule, Carbon $from, int $days): array
  → returns ['created' => int, 'skipped' => int]
```

The command loops over all active schedules and calls `generateForSchedule()` for each. The Filament action calls it once with `$record`. This avoids any logic duplication and makes both callers trivially thin.

### Filament action placement
A `Tables\Actions\Action` (row action) on `DoctorSchedulesTable`, labelled "Generate Slots", with an inline `days` form field (default 14) presented in a modal. On submit it calls `SlotGenerationService::generateForSchedule()` with `$record` and shows a success `Notification` with the created/skipped counts.

*Alternative:* a header action on the list page was considered, but generating for a single schedule is the primary admin use case.

## Risks / Trade-offs

- **Large date ranges** → many DB writes. Mitigation: `--days` defaults to 14; the scheduler never passes a larger value.
- **Concurrent scheduler runs** (e.g., multiple app servers) → duplicate inserts. Mitigation: `firstOrCreate` is atomic per row; worst case is two simultaneous checks, both safe.
- **Schedule changes after slot generation** → stale slots remain. Accepted trade-off for this change; a future "regenerate" action can address it.

## Migration Plan

No schema migrations needed. Deploy, run `php artisan slots:generate` once to backfill the next 14 days, then the scheduler takes over.
