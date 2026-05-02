## 1. Revert Inertia/API Doctor Portal — Delete Files

- [x] 1.1 Delete `app/Http/Controllers/Doctor/DoctorDashboardController.php`
- [x] 1.2 Delete `app/Http/Controllers/Doctor/DoctorScheduleController.php`
- [x] 1.3 Delete `app/Http/Controllers/Doctor/DoctorHistoryController.php`
- [x] 1.4 Delete `app/Http/Controllers/Api/V1/Doctor/DoctorAppointmentStatusController.php`
- [x] 1.5 Delete `app/Http/Controllers/Api/V1/Doctor/DoctorScheduleCrudController.php`
- [x] 1.6 Delete `app/Policies/DoctorSchedulePolicy.php`
- [x] 1.7 Delete `app/Http/Responses/LoginResponse.php`
- [x] 1.8 Delete `resources/js/pages/doctor/Dashboard.vue`
- [x] 1.9 Delete `resources/js/pages/doctor/Schedule.vue`
- [x] 1.10 Delete `resources/js/pages/doctor/History.vue`
- [x] 1.11 Delete `tests/Feature/DoctorWebRoutesTest.php`
- [x] 1.12 Delete `tests/Feature/DoctorAppointmentStatusTest.php`
- [x] 1.13 Delete `tests/Feature/DoctorScheduleManagementTest.php`
- [x] 1.14 Delete `tests/Feature/DoctorLoginRedirectTest.php`

## 2. Revert Inertia/API Doctor Portal — Modify Files

- [x] 2.1 Revert `routes/web.php`: remove the `/doctor/*` web route group; restore the SPA catch-all route to cover all non-API paths
- [x] 2.2 Revert `routes/api.php`: remove the `/api/v1/doctor/*` route group
- [x] 2.3 Revert `app/Providers/FortifyServiceProvider.php`: remove the `LoginResponse` binding
- [x] 2.4 Revert `bootstrap/app.php`: remove the `RoleMiddleware` alias registration
- [x] 2.5 Revert `vite.config.ts`: remove the `@inertiajs/vite` plugin, remove `resources/js/app.ts` entry point, remove `@` alias
- [x] 2.6 Revert `resources/views/app.blade.php`: restore to load `resources/spa/main.ts` (remove any Inertia-specific changes)
- [x] 2.7 Revert `resources/views/spa.blade.php`: remove the `<meta name="csrf-token" ...>` line
- [x] 2.8 Revert `resources/spa/views/LoginView.vue`: remove the `submitFortifyLogin()` function and the 403 catch block

## 3. Create Filament Doctor Panel Provider

- [x] 3.1 Create `app/Providers/Filament/DoctorPanelProvider.php` using `php artisan make:filament-panel doctor` — set path to `doctor`, configure resource discovery to `app/Filament/Doctor/Resources`, configure pages discovery to `app/Filament/Doctor/Pages`
- [x] 3.2 Register `DoctorPanelProvider` in `bootstrap/providers.php`
- [x] 3.3 Update `User::canAccessPanel(Panel $panel): bool` to return `true` for `panel->getId() === 'doctor'` only when `$this->hasRole('doctor')`, and `true` for `panel->getId() === 'admin'` only when `$this->hasRole('admin')`
- [x] 3.4 Verify a doctor user can reach `/doctor/login` and log in

## 4. Create DoctorAppointmentResource

- [x] 4.1 Create `app/Filament/Doctor/Resources/DoctorAppointmentResource.php` with `getEloquentQuery()` scoped to the authenticated doctor's `doctor_id`
- [x] 4.2 Add table columns: patient name (via relationship), service name (via relationship), slot date/time, status badge (using `AppointmentStatus` enum colors)
- [x] 4.3 Create the Edit page: read-only fields for patient, service, slot, and doctor; editable `doctor_notes` textarea
- [x] 4.4 Add `Action::make('markComplete')` table row action — visible only when status is `confirmed`, sets status to `completed`
- [x] 4.5 Add `Action::make('markNoShow')` table row action — visible only when status is `confirmed`, sets status to `no_show`
- [x] 4.6 Disable Create and Delete actions on the resource (doctors cannot create or delete appointments)

## 5. Create DoctorScheduleResource

- [x] 5.1 Create `app/Filament/Doctor/Resources/DoctorScheduleResource.php` with `getEloquentQuery()` scoped to the authenticated doctor's `doctor_id`
- [x] 5.2 Add table columns: day of week, start time, end time, slot duration, active status
- [x] 5.3 Create the Create page form: fields for day, start_time, end_time, slot_duration_minutes, is_active; no doctor_id selector
- [x] 5.4 Override `mutateFormDataBeforeCreate()` on the Create page to inject `doctor_id = auth()->user()->doctor->id`
- [x] 5.5 Create the Edit page form: same fields as Create; no doctor_id selector
- [x] 5.6 Override `mutateFormDataBeforeSave()` on the Edit page to ensure `doctor_id` is not changed
- [x] 5.7 Verify a doctor cannot access another doctor's schedule row via direct URL (Filament's scoped query should return 404)

## 6. Tests

- [x] 6.1 Write a Pest feature test `DoctorPanelAccessTest`: assert a doctor can log in at `/doctor`, a patient cannot access `/doctor`, and an admin cannot access `/doctor`
- [x] 6.2 Write a Pest feature test `DoctorAppointmentResourceTest`: assert appointments list is scoped to the authenticated doctor; assert doctor_notes can be saved; assert Mark Complete and Mark No-Show actions work; assert non-confirmed appointments do not show the actions
- [x] 6.3 Write a Pest feature test `DoctorScheduleResourceTest`: assert schedule list is scoped to the authenticated doctor; assert creating a schedule row forces `doctor_id`; assert a doctor cannot edit/delete another doctor's row

## 7. Final Polish

- [x] 7.1 Run `vendor/bin/pint --dirty --format agent` to fix code style
- [x] 7.2 Run `php artisan test --compact` and confirm all tests pass
- [x] 7.3 Run `php artisan route:list --except-vendor` and verify no leftover `/doctor/*` web or `/api/v1/doctor/*` routes remain, and the new Filament panel routes at `/doctor` are present
