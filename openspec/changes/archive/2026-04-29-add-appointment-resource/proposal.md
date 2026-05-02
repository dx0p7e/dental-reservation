# Proposal: Add Appointment Resource

## What

Add an `AppointmentResource` to the Filament admin panel so clinic staff can view, create, edit, and delete appointments across the system. The resource lives under a new **Appointments** navigation group with sort order 1 and icon `heroicon-o-calendar-days`.

Each appointment record surfaces:
- **Patient** — the `User` who booked (via `patient_id`)
- **Doctor** — via the `Doctor` → `User` relationship
- **Service** — the dental service being performed
- **Slot** — a concrete `ScheduleSlot` (date + start/end time)
- **Status** — `pending`, `confirmed`, `cancelled`, `completed`, `no_show` (enum `AppointmentStatus`)
- **Notes** — optional patient-facing note
- **Doctor notes** — optional internal note

Status badges use `TextColumn->badge()` with colors:
- `pending` → warning
- `confirmed` → success
- `cancelled` → danger
- `completed` → gray
- `no_show` → gray

Also add a `PatientsRelationManager` on `DoctorResource` so staff can see all patients with appointments under a given doctor when viewing the doctor's edit page.

## Why

There is no admin interface for appointments yet. Clinic staff currently have no way to:
- See pending/upcoming appointments across all doctors
- Manually create or reschedule appointments on a patient's behalf
- Update appointment status (confirm, cancel, mark complete)

The `Appointment` model, factory, and migration already exist. This change is purely a Filament layer on top of existing data.

The `PatientsRelationManager` on `DoctorResource` closes the reverse-lookup gap: when reviewing a doctor's profile, staff need to quickly see who their current and past patients are.

## Domain note: existing model shape

The `appointments` table has: `patient_id` (FK → `users`), `doctor_id`, `service_id`, `slot_id` (FK → `schedule_slots`), `status` (enum), `notes`, `doctor_notes`. There is no `requested_datetime` column — the datetime is carried by the associated `ScheduleSlot` (`date` + `start_time`). Form fields should reflect the actual schema.

`AppointmentStatus` enum has five cases: `Pending`, `Confirmed`, `Cancelled`, `Completed`, `NoShow`.

## Non-goals

- Not building a patient-facing booking UI (that is a separate frontend change)
- Not sending email/SMS notifications on status change
- Not enforcing slot availability in the admin resource (staff are trusted actors)
- Not adding any new database migrations

## Success criteria

- Staff can list, create, edit, and delete appointments at `/admin/appointments`
- Status column shows colored badges
- Status can be changed via the edit form
- DoctorResource edit page shows a **Patients** relation manager tab listing all patients with appointments for that doctor
- All standard Filament resource tests pass (list, create renders, edit renders, can create, can edit)
- RelationManager renders test passes
