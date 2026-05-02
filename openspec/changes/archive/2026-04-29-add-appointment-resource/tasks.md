# Tasks: Add Appointment Resource

## Implementation Tasks

### T1 — Create `AppointmentFactory`
- [x] Run `php artisan make:factory AppointmentFactory --model=Appointment --no-interaction`
- [x] Fill in `definition()`: `patient_id => User::factory()`, `doctor_id => Doctor::factory()`, `service_id => Service::factory()`, `slot_id => ScheduleSlot::factory()`, `status => AppointmentStatus::Pending`, `notes => null`, `doctor_notes => null`

---

### T2 — Scaffold `AppointmentResource`
- [x] Run `php artisan make:filament-resource Appointment --generate --no-interaction`
- [x] Open `AppointmentResource.php`: set `$navigationIcon = 'heroicon-o-calendar-days'`, `$navigationGroup = 'Appointments'`, `$navigationSort = 1`
- [x] Replace table columns per design: `patient.name` (sortable+searchable), `doctor.user.name` (sortable+searchable), `service.name` (sortable), `slot.date` (date, sortable), `slot.start_time` (time, sortable), `status` (badge + color map)
- [x] Add `SelectFilter` on `status` using `AppointmentStatus` cases
- [x] Replace form fields per design: `patient_id` Select+searchable, `doctor_id` Select+searchable, `service_id` Select+searchable, `slot_id` Select with formatted label, `status` Select (default pending), `notes` Textarea, `doctor_notes` Textarea

---

### T3 — Create `PatientsRelationManager`
- [x] Run `php artisan make:filament-relation-manager DoctorResource appointments patient --no-interaction`
- [x] Open the generated `PatientsRelationManager.php`: set `protected static ?string $title = 'Patients'`
- [x] Replace table columns: `patient.name`, `patient.email`, `status` (badge with same color map), `slot.date` (date), `slot.start_time` (time)
- [x] Remove create/edit actions — make manager read-only (keep delete action)
- [x] Open `app/Filament/Resources/Doctors/DoctorResource.php`: add `PatientsRelationManager::class` to `getRelations()` return array
- [x] Add `'view' => ViewDoctor::route('/{record}')` to `getPages()` (relation managers require a view/edit page) — check if `ViewDoctor` or `EditDoctor` is already the host

---

### T4 — Run Pint
- [x] Run `vendor/bin/pint app/Filament/Resources/Appointments/ app/Filament/Resources/Doctors/ database/factories/AppointmentFactory.php` to fix style

---

### T5 — Write tests
- [x] Run `php artisan make:test --pest Filament/AppointmentResourceTest --no-interaction`
- [x] Write `beforeEach`: create `admin` role, create+assign admin user, `actingAs`
- [x] Write test: `list page renders`
- [x] Write test: `create page renders`
- [x] Write test: `can create an appointment` — fill all required fields, call `create`, assert record exists
- [x] Write test: `edit page renders` — use `AppointmentFactory` to create record, load edit page
- [x] Write test: `can edit an appointment` — change `status` to `confirmed`, save, assert `fresh()->status === AppointmentStatus::Confirmed`
- [x] Write test: `patients relation manager renders` — create a doctor with appointments, test `PatientsRelationManager` loads
- [x] Run `php artisan test --compact --filter=AppointmentResourceTest` and confirm all pass

---

### T6 — Full regression test
- [x] Run `php artisan test --compact` and confirm all previously passing tests still pass (no regressions) — 51 passed, 22 skipped, 0 failed
