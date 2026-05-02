## Context

The codebase has a complete doctor data model (`Doctor`, `DoctorSchedule`, `ScheduleSlot`, `Appointment`) managed exclusively through the Filament admin panel. A dual role system exists: an `enum('patient','doctor','admin')` column on `users` (legacy) AND Spatie Permission roles (`model_has_roles` table). The patient-facing SPA uses `auth:sanctum` with a hard `hasRole('patient')` gate that returns 403 for any non-patient. The Inertia layer already renders `resources/js/pages/` via a catch-all `/{any}` route in `web.php`. Fortify handles web session auth and currently sends all users to `/dashboard` regardless of role.

## Goals / Non-Goals

**Goals:**
- Give a logged-in doctor three accessible Inertia pages: dashboard (upcoming appointments), schedule management, appointment history
- Block non-doctors from those pages via `role:doctor` Spatie middleware on both web and API route groups
- Add a PATCH endpoint so a doctor can mark appointments `completed` or `no_show`
- Add a data fix so existing `role = 'doctor'` enum users are also assigned the Spatie `doctor` role
- Override Fortify's `LoginResponse` to branch redirect by role

**Non-Goals:**
- Doctor self-registration or profile editing (specialization, bio)
- Slot generation logic (slots are still admin-created via Filament)
- Changes to the patient SPA, its Sanctum API guard, or public doctor read routes
- Changes to Filament doctor/schedule management
- Notifications or mobile-specific layout
- Doctor PATCH for `confirmed` → `cancelled` (cancellation is patient-only per existing policy)

## Decisions

### D1: Inertia pages, not SPA extension

The SPA's `POST /api/v1/auth/login` hard-rejects non-patients (line 42 of `V1/AuthController`). Rather than widening that guard and introducing role branching inside the Vue SPA, the doctor portal is a separate Inertia controller-driven flow under `/doctor/*`. This keeps the patient SPA entirely untouched and follows the existing Inertia convention already used for `/settings/*` and `/dashboard`.

*Alternatives considered:* Adding a separate SPA entrypoint — rejected, it would duplicate the Vite build setup. Widening the patient API guard — rejected, it would complicate the SPA's assumption that the logged-in user is always a patient.

### D2: Spatie `role:doctor` middleware, not enum column check

`role:doctor` (Spatie) is the correct long-term source of truth. Filament's `canAccessPanel()` and the SPA's auth gate both already use Spatie. Using the enum column in middleware would deepen the dual-system problem. The data fix (sync enum → Spatie) resolves any existing mismatch before this code goes live.

*Alternatives considered:* A custom middleware checking `$user->isDoctor()` — rejected, it bypasses the unified Spatie system.

### D3: Doctor data resolved via `$request->user()->doctor`

`User` has `hasOne(Doctor::class)`. From the `Doctor` model, `appointments()`, `schedules()`, and `slots()` relationships are already defined. All new controllers call `$request->user()->doctor` — no new relationships needed. If `doctor` is null (user has the role but no profile row), controllers return 403.

### D4: `DoctorSchedulePolicy` for schedule write operations

Rather than inline `if ($schedule->doctor_id !== $user->doctor->id)` checks in controllers, a policy keeps authorization declarative and testable. The policy checks `$user->doctor?->id === $schedule->doctor_id`. The `before()` hook lets admins bypass.

### D5: Spatie role sync as a one-time migration command

A new artisan command (`doctor:sync-roles`) queries `users` where `role = 'doctor'` and ensures each has the Spatie `doctor` role. This is safer than a raw migration because it uses the `assignRole` method (which handles the pivot correctly) and can be re-run idempotently. It runs once during deploy.

## Risks / Trade-offs

- **Dual role system remains** → Mitigation: `doctor:sync-roles` command closes the gap for existing data; new doctor creation via admin should set both (Filament DoctorResource currently only sets the enum column — a follow-up, not in scope here)
- **`$request->user()->doctor` can be null** → Mitigation: controllers abort with 403 if `$user->doctor` is null; this is a data integrity guard, not expected in normal operation
- **Fortify LoginResponse override affects all roles** → Mitigation: the override is explicit: doctor → `/doctor/dashboard`, admin → `/filament` (already handled by `canAccessPanel`), everyone else → `/dashboard`; the test suite will cover all three branches
- **Inertia pages share the same `web` session auth as the rest of the app** → No isolation risk; `role:doctor` middleware handles the gate

## Migration Plan

1. Run `php artisan doctor:sync-roles` after deploy to fix existing data
2. No database migrations required
3. Rollback: remove the `role:doctor` route groups and revert `FortifyServiceProvider`; existing data is unaffected

## Open Questions

_(none — all decisions made based on audit findings)_
