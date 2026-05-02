# Proposal: Create Schedule Slots Resource

## What

Add a `DoctorScheduleResource` to the Filament admin panel (displayed as **Schedule Slots** in the UI) so clinic staff can define and manage weekly recurring availability windows per doctor. Each window specifies a doctor, a day of the week (ISO 1–7), a start time, an end time, a slot duration in minutes, and an active/inactive toggle.

Overlapping time windows for the same doctor on the same day are blocked at save time with a danger notification.

## Why

Downstream changes depend on this data:

- **Change 8 (AppointmentResource)** — needs schedule slots to associate appointments with availability windows
- **Change 9 (booking API)** — generates bookable `schedule_slots` rows from these weekly templates

Without this admin resource, there is no way for staff to configure doctor availability, making the booking flow impossible to use.

## Domain note: `doctor_schedules` vs `schedule_slots`

The codebase already has two scheduling tables:

| Table | Purpose |
|---|---|
| `doctor_schedules` | Weekly recurring availability templates (`day_of_week`, `start_time`, `end_time`, `is_break`) |
| `schedule_slots` | Concrete date-specific bookable slots generated from templates (`date`, `start_time`, `end_time`, `is_booked`) |

The resource requested maps to the **`doctor_schedules`** model — it's the weekly template, not the concrete slot. However, the existing `doctor_schedules` table is missing two fields the spec requires:

- `slot_duration_minutes` — needed so the booking API knows how to subdivide the availability window
- `is_active` — replaces `is_break` (which has inverse semantics and is too narrow)

A migration will add `slot_duration_minutes` and `is_active` to `doctor_schedules` and drop `is_break`.

Day-of-week convention: existing data uses 0-based (0 = Monday). The migration will not touch existing data convention; display labels in Filament will map 1–7 (ISO) but the stored value will remain 1–7 going forward (migration resets `day_of_week` column to `unsignedTinyInteger` with comment `1=Mon … 7=Sun`).

## Non-goals

- Not generating concrete `schedule_slots` rows (that is Change 9)
- Not validating overlap against already-booked concrete slots
- Not implementing break-time management (the `is_break` concept is removed from scope)

## Success criteria

- Staff can create, edit, and delete schedule slot templates via `/admin/doctor-schedules`
- Saving a slot that overlaps an existing active slot for the same doctor+day shows a danger notification and halts the save
- All five standard Filament resource tests pass (list, create, edit, render ×2)
