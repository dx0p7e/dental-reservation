# Proposal: Create Eloquent Models

## What

Create all Eloquent models for the dental reservation system, covering every table introduced by the `create-database-migrations` change:

- `User` — updated with `MustVerifyEmail`, `HasRoles` (spatie/laravel-permission), new fillable fields, and role-typed relationships
- `Doctor` — doctor profile with schedule and slot relationships
- `Service` — dental service with loyalty rule relationship
- `DoctorSchedule` — recurring weekly availability slots per doctor
- `ScheduleSlot` — concrete bookable slots per doctor per date
- `Appointment` — booking record with `LogsActivity` (spatie/laravel-activitylog) for audit trail
- `LoyaltyTier` — tier definitions (standard / silver / gold)
- `LoyaltyAccount` — per-patient loyalty wallet, auto-created by a `UserObserver` when a patient registers
- `LoyaltyRule` — per-service points/discount rules
- `LoyaltyTransaction` — earn/redeem audit log

A `UserObserver` will be registered in `AppServiceProvider` to automatically create a `LoyaltyAccount` whenever a new `User` with `role = patient` is created.

## Why

Eloquent models are the foundation for every feature: controllers, form requests, policies, jobs, and tests all depend on them. Defining relationships, casts, and fillable attributes in one place ensures:

- **Type safety** — casts make enums, dates, and booleans work correctly throughout the app
- **Consistent access patterns** — relationships mean no raw joins in controllers
- **Audit trail** — activity logging on `Appointment` satisfies compliance requirements with minimal code
- **Role-based access** — `HasRoles` enables gate/policy checks against `patient`, `doctor`, `admin` roles
- **Data integrity** — observer-driven `LoyaltyAccount` creation removes the risk of forgetting to create it in every patient registration path

## Non-goals

- Factories and seeders — separate change
- Form requests and policies — separate change
- API controllers — separate change
- Front-end / Inertia pages — separate change
