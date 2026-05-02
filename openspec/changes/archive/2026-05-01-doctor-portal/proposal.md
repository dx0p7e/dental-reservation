## Why

The doctor role exists in the database and model layer but has no usable portal — a doctor logging in hits a 403 from the SPA's API auth guard, and all three doctor use cases from the thesis (PA016 view appointments, PA017 manage schedule, PA018 view history) are entirely unimplemented. The data tables and Eloquent relationships already exist; this change builds the missing API layer and Inertia portal on top of them.

## What Changes

- Override Fortify's `LoginResponse` contract to redirect doctors to `/doctor/dashboard` (patients stay on `/dashboard`, admins go to `/filament`)
- Add a new web route group `Route::middleware(['auth', 'role:doctor'])->prefix('doctor')` with three Inertia routes: dashboard, schedule, history
- Add a new API route group `Route::middleware(['auth:sanctum', 'role:doctor'])->prefix('api/v1/doctor')` with full CRUD for the doctor's own schedule entries plus appointment status update
- Add three Inertia pages under `resources/js/pages/doctor/`: `Dashboard.vue`, `Schedule.vue`, `History.vue`
- Add new controllers: `DoctorDashboardController`, `DoctorScheduleController`, `DoctorHistoryController`, `DoctorAppointmentStatusController`
- Add `DoctorSchedulePolicy` scoping all write operations to the authenticated doctor's own records
- Add a data fix ensuring that any `users` row with `role = 'doctor'` (enum) also has the Spatie `doctor` role assigned in `model_has_roles`

## Capabilities

### New Capabilities

- `doctor-auth-redirect`: Post-login role-based redirect — doctors routed to `/doctor/dashboard`, patients to `/dashboard`, admins to `/filament`
- `doctor-appointment-view`: Doctor can view their own upcoming confirmed appointments (PA016)
- `doctor-schedule-management`: Doctor can create, update, and delete their own `DoctorSchedule` rows via a new authenticated API (PA017)
- `doctor-appointment-history`: Doctor can view paginated past appointments (completed, no-show, cancelled) (PA018)
- `doctor-appointment-status`: Doctor can mark a confirmed appointment as `completed` or `no_show` via a PATCH endpoint

### Modified Capabilities

_(none — no existing spec-level requirements change; the patient SPA and admin Filament panel are untouched)_

## Impact

- **`routes/api.php`**: new `api/v1/doctor` route group added
- **`routes/web.php`**: new `/doctor/*` Inertia route group added
- **`app/Providers/FortifyServiceProvider.php`**: `LoginResponse` binding added
- **`app/Http/Controllers/`**: four new doctor controllers
- **`app/Policies/DoctorSchedulePolicy.php`**: new policy
- **`resources/js/pages/doctor/`**: three new Vue pages
- **`database/seeders/` or migration**: Spatie role sync for existing doctor users
- No changes to: patient SPA, Filament panel, migration table structure, slot generation logic, or public doctor read API
