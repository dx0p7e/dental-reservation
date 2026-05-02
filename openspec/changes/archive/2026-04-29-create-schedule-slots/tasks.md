# Tasks: Create Schedule Slots Resource

## Implementation Tasks

### T1 — Migrate `doctor_schedules` table
- [x] Run `php artisan make:migration update_doctor_schedules_add_slot_fields --no-interaction`
- [x] Write migration: add `slot_duration_minutes` (unsignedSmallInteger, not null), add `is_active` (boolean, default true), drop `is_break`
- [x] Run `php artisan migrate --no-interaction` to apply

---

### T2 — Update `DoctorSchedule` model
- [x] Open `app/Models/DoctorSchedule.php`
- [x] Replace `$fillable`: add `slot_duration_minutes`, `is_active`; remove `is_break`
- [x] Replace `$casts`: remove `is_break`, add `slot_duration_minutes => 'integer'`, `is_active => 'boolean'`

---

### T3 — Create `DoctorScheduleFactory`
- [x] Run `php artisan make:factory DoctorScheduleFactory --model=DoctorSchedule --no-interaction`
- [x] Fill in `definition()`: `doctor_id => Doctor::factory()`, `day_of_week => fake()->numberBetween(1,7)`, `start_time => '09:00'`, `end_time => '17:00'`, `slot_duration_minutes => 30`, `is_active => true`

---

### T4 — Scaffold `DoctorScheduleResource`
- [x] Run `php artisan make:filament-resource DoctorSchedule --generate --no-interaction`
- [x] Open `DoctorScheduleResource.php`: set `$navigationIcon = 'heroicon-o-clock'`, `$navigationGroup = 'Staff'`, `$navigationSort = 2`, `$navigationLabel = 'Schedule Slots'`
- [x] Replace table columns per design: `doctor.name` (sortable+searchable), `day_of_week` (badge + day label mapping + colors), `start_time` (time, sortable), `end_time` (time, sortable), `slot_duration_minutes` (suffix `min`, sortable), `is_active` (IconColumn boolean, sortable)
- [x] Add `SelectFilter` on `doctor_id` (relationship) and `SelectFilter` on `is_active` to table filters
- [x] Replace form fields per design: `doctor_id` Select+relationship+searchable, `day_of_week` Select (1–7 with names), `start_time` TimePicker, `end_time` TimePicker, `slot_duration_minutes` TextInput (numeric, min 1, suffix `min`), `is_active` Toggle (default true)

---

### T5 — Implement overlap validation
- [x] Open `app/Filament/Resources/DoctorSchedules/Pages/CreateDoctorSchedule.php`
- [x] Override `beforeCreate()`: query `DoctorSchedule` for same `doctor_id` + `day_of_week` with overlapping time range; if found, fire danger `Notification` and call `$this->halt()`
- [x] Open `app/Filament/Resources/DoctorSchedules/Pages/EditDoctorSchedule.php`
- [x] Override `beforeSave()`: same overlap query but exclude current record (`id != $this->record->id`); if found, fire danger `Notification` and call `$this->halt()`

---

### T6 — Run Pint
- [ ] Run `vendor/bin/pint app/Filament/Resources/DoctorSchedules/ app/Models/DoctorSchedule.php database/factories/DoctorScheduleFactory.php` to fix style

---

### T7 — Write tests for `DoctorScheduleResource`
- [ ] Run `php artisan make:test --pest Filament/DoctorScheduleResourceTest --no-interaction`
- [ ] Write `beforeEach`: create `admin` role, create+assign admin user, `actingAs`
- [ ] Write standard 5 tests: list renders, create renders, can create, edit renders, can edit
- [ ] Write overlap test: create a slot, attempt to create overlapping slot for same doctor+day, assert no second record was created (or assert notification sent)
- [ ] Write non-overlap test: slots on different days for same doctor are allowed
- [ ] Run `php artisan test --compact --filter=DoctorScheduleResourceTest` and confirm all pass

---

### T8 — Full regression test
- [ ] Run `php artisan test --compact` and confirm all previously passing tests still pass (no regressions)
