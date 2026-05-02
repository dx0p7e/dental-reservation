# Tasks: Booking API

## Implementation Tasks

### T1 — Auth Controller & Routes
- [ ] Run `php artisan make:controller Api/V1/AuthController --no-interaction`
- [ ] Implement `register`: validate via `RegisterRequest`, create `User`, `assignRole('patient')`, create `LoyaltyAccount` with `points_balance=0`, issue Sanctum token named `"booking-api"`, return `{ token, user }` 201
- [ ] Implement `login`: validate via `LoginRequest`, check `Auth::attempt()`, check `hasRole('patient')` (403 if not), issue Sanctum token, return `{ token, user }`
- [ ] Implement `logout`: revoke `$request->user()->currentAccessToken()->delete()`, return 204
- [ ] Run `php artisan make:request Api/V1/RegisterRequest --no-interaction` and fill validation rules
- [ ] Run `php artisan make:request Api/V1/LoginRequest --no-interaction` and fill validation rules
- [ ] Add `/api/v1/` route group to `routes/api.php` with auth routes

---

### T2 — Doctor & Slot Controllers
- [ ] Run `php artisan make:controller Api/V1/DoctorController --no-interaction`
- [ ] Implement `DoctorController@index`: `Doctor::where('is_active', true)->with('user')->get()` → `DoctorResource` collection
- [ ] Run `php artisan make:resource Api/V1/DoctorResource --no-interaction` and define fields: `id`, `name` (from `user.name`), `specialization`, `bio`
- [ ] Run `php artisan make:controller Api/V1/SlotController --no-interaction`
- [ ] Implement `SlotController@index`: scoped to `{doctor}` route model binding, filter `is_booked = false`, optional `?date=` query param, order by `date, start_time` → `SlotResource` collection
- [ ] Run `php artisan make:resource Api/V1/SlotResource --no-interaction` and define fields: `id`, `date`, `start_time`, `end_time`
- [ ] Register `GET /api/v1/doctors` and `GET /api/v1/doctors/{doctor}/slots` routes

---

### T3 — Service Controller
- [ ] Run `php artisan make:controller Api/V1/ServiceController --no-interaction`
- [ ] Implement `ServiceController@index`: `Service::all()` → `ServiceResource` collection
- [ ] Run `php artisan make:resource Api/V1/ServiceResource --no-interaction` and define fields: `id`, `name`, `description`, `duration_minutes`, `price`
- [ ] Register `GET /api/v1/services` route

---

### T4 — Appointment Controller & Policy
- [ ] Run `php artisan make:policy AppointmentPolicy --model=Appointment --no-interaction`
- [ ] Implement `AppointmentPolicy@view` and `AppointmentPolicy@delete`: `$user->id === $appointment->patient_id`
- [ ] Run `php artisan make:controller Api/V1/AppointmentController --no-interaction`
- [ ] Implement `AppointmentController@index`: `$request->user()->appointments()->with(['doctor.user','service','slot'])->latest()->get()` → `AppointmentResource` collection
- [ ] Run `php artisan make:request Api/V1/StoreAppointmentRequest --no-interaction` and fill rules: `doctor_id` exists:doctors,id; `service_id` exists:services,id; `slot_id` exists:schedule_slots,id
- [ ] Implement `AppointmentController@store`: wrap in `DB::transaction()`, `ScheduleSlot::where('id',...)->where('is_booked',false)->lockForUpdate()->firstOrFail()`, set `is_booked=true`, create `Appointment` with `status=Pending` → `AppointmentResource` 201
- [ ] Implement `AppointmentController@destroy`: authorize via policy, check status is `Pending` or `Confirmed` (422 otherwise), set `status=Cancelled`, return 204
- [ ] Run `php artisan make:resource Api/V1/AppointmentResource --no-interaction` and define nested fields per design
- [ ] Register `GET/POST /api/v1/appointments` and `DELETE /api/v1/appointments/{appointment}` behind `auth:sanctum`

---

### T5 — Loyalty Controller
- [ ] Run `php artisan make:controller Api/V1/LoyaltyController --no-interaction`
- [ ] Implement `LoyaltyController@show`: return `$request->user()->loyaltyAccount` or default zero-balance response → `LoyaltyResource`
- [ ] Run `php artisan make:resource Api/V1/LoyaltyResource --no-interaction` and define fields: `points_balance`, `tier`
- [ ] Register `GET /api/v1/loyalty` behind `auth:sanctum`

---

### T6 — Run Pint
- [ ] Run `vendor/bin/pint app/Http/Controllers/Api/ app/Http/Requests/Api/ app/Http/Resources/Api/ app/Policies/AppointmentPolicy.php` to fix style

---

### T7 — Write tests
- [ ] Run `php artisan make:test --pest Api/V1/AuthApiTest --no-interaction`
- [ ] Write auth tests: register creates patient with token; login returns token; non-patient login gets 403; logout revokes token
- [ ] Run `php artisan make:test --pest Api/V1/DoctorApiTest --no-interaction`
- [ ] Write doctor tests: list returns only active doctors; slots filtered by `is_booked=false`; `?date=` filter works
- [ ] Run `php artisan make:test --pest Api/V1/AppointmentApiTest --no-interaction`
- [ ] Write appointment tests: patient can create booking; double-booking attempt returns 404; patient cannot see other patient's appointments; patient can cancel pending appointment; patient cannot cancel completed appointment; non-owner cancel returns 403
- [ ] Run `php artisan make:test --pest Api/V1/LoyaltyApiTest --no-interaction`
- [ ] Write loyalty tests: returns balance and tier; unauthenticated returns 401
- [ ] Run `php artisan test --compact --filter=Api` and confirm all pass

---

### T8 — Full regression test
- [ ] Run `php artisan test --compact` and confirm all previously passing tests still pass
