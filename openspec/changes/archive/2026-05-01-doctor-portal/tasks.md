## 1. Data Fix — Spatie Role Sync

- [x] 1.1 Create artisan command `doctor:sync-roles`
- [x] 1.2 Write a feature test verifying the command assigns the Spatie `doctor` role
- [x] 1.3 Run the command against the local database to confirm existing doctor accounts are synced

## 2. Auth Redirect

- [x] 2.1 Bind a custom `LoginResponse` implementation in `FortifyServiceProvider` that redirects doctors to `/doctor/dashboard`, admins to `/filament`, and everyone else to `/dashboard`
- [x] 2.2 Write a feature test covering all three redirect branches (doctor, admin, patient)

## 3. Web Routes and Inertia Controllers

- [x] 3.1 In `routes/web.php`, wrap the three Inertia routes inside `Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')` — apply the middleware array inline, no alias registration needed: `GET /doctor/dashboard`, `GET /doctor/schedule`, `GET /doctor/history`
- [x] 3.2 Create `DoctorDashboardController@index` — loads the doctor's upcoming (`pending`/`confirmed`) appointments eager-loading `patient`, `service`, `slot`; returns `Inertia::render('doctor/Dashboard', [...])`
- [x] 3.3 Create `DoctorScheduleController@index` — loads the doctor's `DoctorSchedule` rows; returns `Inertia::render('doctor/Schedule', [...])`
- [x] 3.4 Create `DoctorHistoryController@index` — loads paginated past (`completed`/`no_show`/`cancelled`) appointments; returns `Inertia::render('doctor/History', [...])`
- [x] 3.5 Write feature tests for all three Inertia routes: unauthenticated → redirect, patient → 403, doctor → 200 with correct component name

## 4. API Routes — Appointments

- [x] 4.1 Add `api/v1/doctor` prefix group with `['auth:sanctum', 'role:doctor']` middleware in `routes/api.php`
- [x] 4.2 Create `DoctorAppointmentStatusController@update` — validates `status` is `completed` or `no_show`, verifies appointment is `confirmed`, verifies `appointment->doctor_id === $user->doctor->id` (403 otherwise), updates status; returns 200
- [x] 4.3 Register `PATCH api/v1/doctor/appointments/{appointment}/status` pointing to the status controller
- [x] 4.4 Write feature tests for the status PATCH: success completed, success no_show, invalid status 422, wrong state 422, wrong doctor 403, unauthenticated 401, patient 403

## 5. API Routes — Schedule Management

- [x] 5.1 Create `DoctorSchedulePolicy` with `before()` (admin bypass), `view()`, `create()`, `update()`, `delete()` — all write methods check `$user->doctor?->id === $schedule->doctor_id`
- [x] 5.2 Register the policy in `AuthServiceProvider` (or via model discovery)
- [x] 5.3 Add `GET /api/v1/doctor/schedules` — returns the authenticated doctor's own schedules
- [x] 5.4 Add `POST /api/v1/doctor/schedules` — creates a new `DoctorSchedule`, always forcing `doctor_id` to the authenticated doctor's ID; validates `day_of_week` (1–7), `start_time`, `end_time`, `slot_duration_minutes` (positive int)
- [x] 5.5 Add `PUT /api/v1/doctor/schedules/{schedule}` — updates own schedule (policy authorization)
- [x] 5.6 Add `DELETE /api/v1/doctor/schedules/{schedule}` — deletes own schedule (policy authorization); returns 204
- [x] 5.7 Write feature tests for all schedule endpoints: list own, create forces own doctor_id, update own → 200, update other's → 403, delete own → 204, delete other's → 403

## 6. Vue Pages

- [x] 6.1 Create `resources/js/pages/doctor/Dashboard.vue` — renders a card list of upcoming appointments; each card shows patient name, service, slot date/time, status badge; `confirmed` appointments show "Mark Complete" and "Mark No-Show" buttons that call `PATCH .../status`
- [x] 6.2 Create `resources/js/pages/doctor/Schedule.vue` — renders a table of schedule rows (day, start, end, slot duration, active); "Add" opens an inline form row; each row has edit/delete controls; form submits to schedule API endpoints
- [x] 6.3 Create `resources/js/pages/doctor/History.vue` — renders a paginated table of past appointments: date, patient name, service, final status
- [x] 6.4 Build the frontend assets and confirm no TypeScript/build errors

## 7. Code Quality

- [x] 7.1 Run `vendor/bin/pint --dirty` and fix any formatting issues
- [x] 7.2 Run the full test suite (`php artisan test --compact`) and confirm all tests pass
