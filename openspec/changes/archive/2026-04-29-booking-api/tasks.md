# Tasks: Booking API

## Implementation Tasks

### T1 — Auth Controller & Routes
- [x] Run `php artisan make:controller Api/V1/AuthController --no-interaction`
- [x] Implement `register`: validate via `RegisterRequest`, create `User`, `assignRole('patient')`, create `LoyaltyAccount` with `points_balance=0`, issue Sanctum token named `"booking-api"`, return `{ token, user }` 201
- [x] Implement `login`: validate via `LoginRequest`, check `Auth::attempt()`, check `hasRole('patient')` (403 if not), issue Sanctum token, return `{ token, user }`
- [x] Implement `logout`: revoke `$request->user()->currentAccessToken()->delete()`, return 204
- [x] Run `php artisan make:request Api/V1/RegisterRequest --no-interaction` and fill validation rules
- [x] Run `php artisan make:request Api/V1/LoginRequest --no-interaction` and fill validation rules
- [x] Add `/api/v1/` route group to `routes/api.php` with auth routes

---

### T2 — Doctor & Slot Controllers
- [x] Run `php artisan make:controller Api/V1/DoctorController --no-interaction`
- [x] Implement `DoctorController@index`: `Doctor::where('is_active', true)->with('user')->get()` → `DoctorResource` collection
- [x] Run `php artisan make:resource Api/V1/DoctorResource --no-interaction` and define fields: `id`, `name` (from `user.name`), `specialization`, `bio`
- [x] Run `php artisan make:controller Api/V1/SlotController --no-interaction`
- [x] Implement `SlotController@index`: scoped to `{doctor}` route model binding, filter `is_booked = false`, optional `?date=` query param, order by `date, start_time` → `SlotResource` collection
- [x] Run `php artisan make:resource Api/V1/SlotResource --no-interaction` and define fields: `id`, `date`, `start_time`, `end_time`
- [x] Register `GET /api/v1/doctors` and `GET /api/v1/doctors/{doctor}/slots` routes

---

### T3 — Service Controller
- [x] Run `php artisan make:controller Api/V1/ServiceController --no-interaction`
- [x] Implement `ServiceController@index`: `Service::all()` → `ServiceResource` collection
- [x] Run `php artisan make:resource Api/V1/ServiceResource --no-interaction` and define fields: `id`, `name`, `description`, `duration_minutes`, `price`
- [x] Register `GET /api/v1/services` route

---

### T4 — Appointment Controller & Policy
- [x] Run `php artisan make:policy AppointmentPolicy --model=Appointment --no-interaction`
- [x] Implement `AppointmentPolicy@view` and `AppointmentPolicy@delete`: `$user->id === $appointment->patient_id`; add `before()` hook granting admins full access
- [x] Run `php artisan make:controller Api/V1/AppointmentController --no-interaction`
- [x] Implement `AppointmentController@index`: `$request->user()->appointments()->with(['doctor.user','service','slot'])->latest()->get()` → `AppointmentResource` collection
- [x] Run `php artisan make:request Api/V1/StoreAppointmentRequest --no-interaction` and fill rules: `doctor_id` exists:doctors,id; `service_id` exists:services,id; `slot_id` exists:schedule_slots,id
- [x] Implement `AppointmentController@store`: wrap in `DB::transaction()`, `ScheduleSlot::where('id',...)->where('is_booked',false)->lockForUpdate()->firstOrFail()`, set `is_booked=true`, create `Appointment` with `status=Pending` → `AppointmentResource` 201
- [x] Implement `AppointmentController@destroy`: authorize via `Gate::authorize('delete', $appointment)`, check status is `Pending` or `Confirmed` (422 otherwise), set `status=Cancelled`, return 204
- [x] Run `php artisan make:resource Api/V1/AppointmentResource --no-interaction` and define nested fields per design
- [x] Register `GET/POST /api/v1/appointments` and `DELETE /api/v1/appointments/{appointment}` behind `auth:sanctum`

---

### T5 — Loyalty Controller
- [x] Run `php artisan make:controller Api/V1/LoyaltyController --no-interaction`
- [x] Implement `LoyaltyController@show`: return `$request->user()->loyaltyAccount` or default zero-balance response → `LoyaltyResource`
- [x] Run `php artisan make:resource Api/V1/LoyaltyResource --no-interaction` and define fields: `points_balance`, `tier`
- [x] Register `GET /api/v1/loyalty` behind `auth:sanctum`

---

### T6 — Run Pint
- [x] Run `vendor/bin/pint app/Http/Controllers/Api/ app/Http/Requests/Api/ app/Http/Resources/Api/ app/Policies/AppointmentPolicy.php` to fix style

---

### T7 — Write tests
- [x] Run `php artisan make:test --pest Api/V1/AuthApiTest --no-interaction`
- [x] Write auth tests: register creates patient with token; login returns token; non-patient login gets 403; logout revokes token
- [x] Run `php artisan make:test --pest Api/V1/DoctorApiTest --no-interaction`
- [x] Write doctor tests: list returns only active doctors; slots filtered by `is_booked=false`; `?date=` filter works
- [x] Run `php artisan make:test --pest Api/V1/AppointmentApiTest --no-interaction`
- [x] Write appointment tests: patient can create booking; double-booking attempt returns 404; patient cannot see other patient's appointments; patient can cancel pending appointment; patient cannot cancel completed appointment; non-owner cancel returns 403
- [x] Run `php artisan make:test --pest Api/V1/LoyaltyApiTest --no-interaction`
- [x] Write loyalty tests: returns balance and tier; unauthenticated returns 401
- [x] Run `php artisan test --compact --filter=Api` and confirm all pass

---

### T8 — Full regression test
- [x] Run `php artisan test --compact` and confirm all previously passing tests still pass