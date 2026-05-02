## Why

The first doctor-portal implementation built a custom Inertia SPA + API routes approach that created a fragile dual-auth system (SPA Sanctum tokens competing with Fortify web sessions). Doctors could not reliably log in, and the login fallback was brittle. Filament's multi-panel architecture is a cleaner fit — it provides its own login, navigation, and CRUD scaffolding out of the box, eliminating the need for a custom frontend entirely.

## What Changes

**Revert (remove the Inertia/API doctor portal):**
- Remove `DoctorDashboardController`, `DoctorScheduleController`, `DoctorHistoryController`
- Remove `DoctorAppointmentStatusController`, `DoctorScheduleCrudController`
- Remove `DoctorSchedulePolicy`
- Remove `LoginResponse` and its `FortifyServiceProvider` binding
- Remove all `/doctor/*` web routes and `/api/v1/doctor/*` API routes
- Remove `resources/js/pages/doctor/` Vue pages
- Revert `vite.config.ts` (remove Inertia plugin, `resources/js/app.ts` entry, `@` alias)
- Revert `resources/views/app.blade.php` (back to loading `resources/spa/main.ts`)
- Revert `resources/views/spa.blade.php` (remove `csrf-token` meta tag)
- Revert `resources/spa/views/LoginView.vue` (remove 403 Fortify fallback)
- Remove test files: `DoctorWebRoutesTest`, `DoctorAppointmentStatusTest`, `DoctorScheduleManagementTest`, `DoctorLoginRedirectTest`

**Build (new Filament doctor panel):**
- Add `DoctorPanelProvider` at path `/doctor` with its own login page, navigation, and resource discovery
- Update `canAccessPanel()` on `User` to branch on panel ID — doctors access `/doctor`, admins access `/admin`
- Create `app/Filament/Doctor/Resources/DoctorAppointmentResource` — doctors see only their own appointments; edit page exposes `doctor_notes` field and status action buttons (Mark Complete, Mark No-Show)
- Create `app/Filament/Doctor/Resources/DoctorScheduleResource` — doctors manage only their own schedule rows; `doctor_id` is always forced to the authenticated doctor

**Keep (from previous implementation):**
- `DoctorObserver` — sets `role='doctor'` and assigns Spatie role when a Doctor profile is created
- `UserObserver` role sync — assigns Spatie role on user creation for `doctor`/`admin` enum values
- `SyncDoctorRoles` artisan command + its test
- `SyncDoctorRolesCommandTest`

## Capabilities

### New Capabilities

- `doctor-panel-access`: Doctor can log in at `/doctor` via Filament's built-in login and access a role-scoped panel (appointments + schedule only)
- `doctor-appointment-management`: Doctor can view their own upcoming appointments, add doctor notes, and mark appointments as completed or no-show
- `doctor-schedule-management`: Doctor can create, update, and delete their own schedule rows from the Filament panel; `doctor_id` is always forced to their own

### Modified Capabilities

_(none — patient SPA and admin Filament panel are untouched; no existing spec-level requirements change)_

## Impact

- **`app/Providers/Filament/DoctorPanelProvider.php`**: new file
- **`app/Models/User.php`**: `canAccessPanel()` updated to branch by panel ID
- **`app/Filament/Doctor/`**: new resource directory, untouched by admin panel discovery
- **`app/Providers/FortifyServiceProvider.php`**: `LoginResponse` binding removed
- **`app/Http/Responses/LoginResponse.php`**: deleted
- **`routes/web.php`**: `/doctor/*` group removed; catch-all regex reverted
- **`routes/api.php`**: `/api/v1/doctor/*` group removed
- **`vite.config.ts`**: Inertia plugin, `resources/js/app.ts`, and `@` alias removed
- **`resources/views/app.blade.php`**: reverted to `resources/spa/main.ts`
- **`resources/views/spa.blade.php`**: `csrf-token` meta removed
- **`resources/spa/views/LoginView.vue`**: 403 fallback logic removed
- No changes to migrations, seeders, patient SPA, or admin Filament panel resources
