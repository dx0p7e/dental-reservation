## 1. Database

- [x] 1.1 Create migration `php artisan make:migration create_doctor_service_table --no-interaction` with `doctor_id` (FK → doctors, cascade delete), `service_id` (FK → services, cascade delete), and composite primary key on `(doctor_id, service_id)` — no timestamps
- [x] 1.2 Run `php artisan migrate` to apply the pivot migration

## 2. Model Relationships

- [x] 2.1 Add `services(): BelongsToMany` relationship to `Doctor` model using `'doctor_service'` as the pivot table
- [x] 2.2 Add `doctors(): BelongsToMany` relationship to `Service` model using `'doctor_service'` as the pivot table

## 3. Admin Filament — DoctorResource

- [x] 3.1 Add `Select::make('services')->multiple()->relationship('services', 'name')` to `DoctorForm::configure()` in `app/Filament/Resources/Doctors/Schemas/DoctorForm.php`

## 4. Backend — API

- [x] 4.1 Update `DoctorController::index()` to eager-load `services` and their `loyaltyRule` alongside `user`: `with(['user', 'services.loyaltyRule'])`
- [x] 4.2 Update `DoctorResource::toArray()` to include `'services' => ServiceResource::collection($this->services)` (or inline array map) returning `{ id, name, price, loyalty_discount_pct }` per service
- [x] 4.3 Open `app/Http/Resources/Api/V1/ServiceResource.php` (it already exists). Fix the `loyalty_discount_pct` field — it currently reads from `$request->attributes`, which always returns `null`. Change it to `$this->loyaltyRule?->discount_pct`. Verify the resource exposes all four required fields: `id`, `name`, `price`, `loyalty_discount_pct`. The `Service` model must eager-load `loyaltyRule` wherever `ServiceResource` is used.
- [x] 4.4 Add `services(Doctor $doctor): JsonResponse` method to `DoctorController` using route model binding (consistent with `show(Doctor $doctor)` already in the controller). Return `ServiceResource::collection($doctor->services->loadMissing('loyaltyRule'))`.
- [x] 4.5 Register route `Route::get('doctors/{doctor}/services'` [V1DoctorController::class, 'services'])->name('doctors.services.index')` inside the `v1` prefix group in `routes/api.php` (public, no auth middleware)

## 5. Frontend — DoctorsView

- [x] 5.1 Update `DoctorsView.vue` to use `authStore.isAuthenticated` to conditionally render the service chip filter row (authenticated only)
- [x] 5.2 Add a `selectedServiceId` ref (`ref<number | null>(null)`) and a `filteredDoctors` computed property: when `selectedServiceId` is `null` return all `doctors`; otherwise filter to doctors where `d.services.some(s => s.id === selectedServiceId.value)`. Render a horizontal chip row above the list (authenticated only): an "All" chip that sets `selectedServiceId` to `null`, plus one chip per unique service across all doctors that sets `selectedServiceId` to that service's `id`. Replace `v-for="doctor in doctors"` with `v-for="doctor in filteredDoctors"`.
- [x] 5.3 Add service badges to each doctor card (both authenticated and guest views) showing the doctor's assigned services
- [x] 5.4 For guest users: replace the "View Slots" button with a "Log in to book" secondary button (RouterLink → `/login`)
- [x] 5.5 For authenticated users: keep "View Slots" button as-is

## 6. Frontend — DoctorSlotsView

- [x] 6.1 Replace the `fetchServices()` call in `DoctorSlotsView.vue` that fetches all services with a call to `GET /api/v1/doctors/{doctorId}/services`
- [x] 6.2 Add empty state when `services.length === 0` after fetch: hide the service `<select>` and show "No services available for this doctor. Contact the clinic."

## 7. Tests

- [x] 7.1 Write Pest migration test: `doctor_service` table exists with correct columns and foreign keys
- [x] 7.2 Write Pest feature test: `GET /api/v1/doctors` response includes `services` array per doctor
- [x] 7.3 Write Pest feature test: `GET /api/v1/doctors/{doctor}/services` returns only that doctor's services
- [x] 7.4 Write Pest feature test: `GET /api/v1/doctors/{doctor}/services` returns empty array for doctor with no services
- [x] 7.5 Write Pest feature test: `GET /api/v1/doctors/9999/services` returns 404

## 8. Code Quality

- [x] 8.1 Run `vendor/bin/pint --dirty --format agent` to fix PHP code style
- [x] 8.2 Run `php artisan test --compact` and confirm all tests pass
