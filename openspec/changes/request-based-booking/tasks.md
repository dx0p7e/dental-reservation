## 1. Database Migration

- [x] 1.1 Run `php artisan make:migration --no-interaction make_slot_and_doctor_nullable_add_preferred_date_to_appointments_table` and implement: `->nullable()->change()` for `slot_id` and `doctor_id`, add `$table->date('preferred_date')->nullable()->after('slot_id')`
- [x] 1.2 Update `Appointment` model `$fillable` to include `preferred_date` and `doctor_id` (already present but verify), and remove any `NOT NULL` assumptions from casts
- [x] 1.3 Run `php artisan migrate --no-interaction` and verify the schema change applies cleanly

## 2. API — Booking Request Endpoint

- [x] 2.1 Run `php artisan make:request Api/V1/StoreAppointmentRequestRequest --no-interaction` and add rules: `service_id` required/integer/exists:services,id; `preferred_date` required/date/after_or_equal:today; `notes` nullable/string/max:1000
- [x] 2.2 Add `requestStore` method to `App\Http\Controllers\Api\V1\AppointmentController`: create `Appointment` with `patient_id = $request->user()->id`, `service_id`, `preferred_date`, `notes`, `status = Pending`, `slot_id = null`, `doctor_id = null`; return `AppointmentResource` 201
- [x] 2.3 Register `POST /api/v1/appointments/request` in `routes/api.php` inside the `auth:sanctum` middleware group, pointing to `AppointmentController@requestStore`
- [x] 2.4 Update `App\Http\Resources\Api\V1\AppointmentResource` to handle nullable `slot` and `doctor` relationships (return `null` instead of accessing properties on null)
- [x] 2.5 Update `resources/spa/types/index.ts` — make `Appointment.slot` and `Appointment.doctor` nullable: `slot: { id: number; date: string; start_time: string } | null` and `doctor: { id: number; name: string } | null`

## 3. Filament — Admin Confirm Action

- [x] 3.1 Run `php artisan make:filament-action ConfirmAppointmentRequestAction --no-interaction` (or create the class manually at `app/Filament/Resources/Appointments/Actions/ConfirmAppointmentRequestAction.php` following existing action conventions)
- [x] 3.2 Implement the action: visible only when `$record->slot_id === null && $record->status === AppointmentStatus::Pending`; modal form with `Select::make('doctor_id')` (active doctors) and `Select::make('slot_id')` (available future slots for selected doctor, reactive on doctor change); on action: wrap all writes in `DB::transaction` — call `$record->update(['doctor_id' => ..., 'slot_id' => ..., 'status' => Confirmed])` first (single save so the observer fires with slot_id already set on the model), then `ScheduleSlot::find($slotId)->update(['is_booked' => true])`; if either fails the transaction rolls back and the observer email is not sent
- [x] 3.3 Register `ConfirmAppointmentRequestAction` in `AppointmentsTable::configure()` inside `recordActions()`
- [x] 3.4 Update `AppointmentForm` — make `slot_id` and `doctor_id` selects non-required (since request-based records have `null` values; use `->nullable()` on both selects)

## 4. Patient SPA — Request Booking UI

- [x] 4.1 Create `resources/spa/views/RequestBookingView.vue`: form with service `<select>` (fetched from `GET /api/v1/services`), preferred date `<input type="date">` (min=today), optional notes `<textarea>`; on submit call `POST /api/v1/appointments/request`, show success message, redirect to `/appointments`
- [x] 4.2 Register `/request-appointment` route in `resources/spa/router/index.ts` pointing to `RequestBookingView`, with `meta: { requiresAuth: true }`
- [x] 4.3 Add a "Request Appointment" link/button to `AppointmentsView.vue` (e.g. near the page header) that navigates to `/request-appointment`
- [x] 4.4 Update `AppointmentsView.vue` to null-guard `appt.slot` and `appt.doctor` — display "Pending assignment" or similar when both are null

## 5. Tests

- [x] 5.1 Add to `tests/Feature/Api/V1/AppointmentApiTest.php`: test patient can submit a booking request (201, slot_id null, doctor_id null); test past preferred_date rejected (422); test unauthenticated rejected (401); test missing service_id rejected (422)
- [x] 5.2 Add to `tests/Feature/Api/V1/AppointmentApiTest.php`: test that a confirmed appointment returns null-safe slot/doctor in the resource when slot_id is null
- [x] 5.3 Run `php artisan test --compact --filter=AppointmentApiTest` and confirm all tests pass
- [x] 5.4 Run `vendor/bin/pint --dirty --format agent` to fix any formatting issues
