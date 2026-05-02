# Design: Booking API

## Directory Structure

```
app/Http/Controllers/Api/V1/
    AuthController.php          — register, login, logout
    DoctorController.php        — index
    SlotController.php          — index (scoped to doctor)
    ServiceController.php       — index
    AppointmentController.php   — index, store, destroy
    LoyaltyController.php       — show

app/Http/Requests/Api/V1/
    RegisterRequest.php
    LoginRequest.php
    StoreAppointmentRequest.php

app/Http/Resources/Api/V1/
    DoctorResource.php
    SlotResource.php
    ServiceResource.php
    AppointmentResource.php
    LoyaltyResource.php

app/Policies/
    AppointmentPolicy.php
```

## Routing

Added as a new route group in `routes/api.php`:

```php
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login',    [AuthController::class, 'login'])->name('login');
        Route::post('logout',   [AuthController::class, 'logout'])
             ->middleware('auth:sanctum')->name('logout');
    });

    Route::get('doctors',                  [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('doctors/{doctor}/slots',   [SlotController::class, 'index'])->name('doctors.slots.index');
    Route::get('services',                 [ServiceController::class, 'index'])->name('services.index');

    // Patient-authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('appointments',               [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments',              [AppointmentController::class, 'store'])->name('appointments.store');
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
        Route::get('loyalty',                    [LoyaltyController::class, 'show'])->name('loyalty.show');
    });
});
```

## Auth Design

`Api\V1\AuthController` is separate from the existing session-based `AuthController`:

- **register**: validates, creates `User`, assigns `patient` role via Spatie Permission, creates `LoyaltyAccount` with `points_balance = 0`, returns `{ token, user }` with 201
- **login**: validates credentials, checks user `hasRole('patient')` (non-patients get 403), issues Sanctum token via `$user->createToken('api')->plainTextToken`, returns `{ token, user }`
- **logout**: revokes `$request->user()->currentAccessToken()->delete()`

Token name: `"booking-api"` for traceability.

## Controller Designs

### DoctorController@index
```
Doctor::where('is_active', true)->with('user')->get()
→ DoctorResource collection
```
Fields: `id`, `name` (from `user.name`), `specialization`, `bio`

### SlotController@index (GET /doctors/{doctor}/slots)
```
$doctor->slots()
    ->where('is_booked', false)
    ->when($request->date, fn($q) => $q->whereDate('date', $request->date))
    ->orderBy('date')->orderBy('start_time')
    ->get()
→ SlotResource collection
```
Fields: `id`, `date`, `start_time`, `end_time`

Route model binding resolves `Doctor` automatically.

### ServiceController@index
```
Service::all() → ServiceResource collection
```
Fields: `id`, `name`, `description`, `duration_minutes`, `price`

### AppointmentController@index
```
$request->user()->appointments()
    ->with(['doctor.user', 'service', 'slot'])
    ->latest()
    ->get()
→ AppointmentResource collection
```

### AppointmentController@store
Uses `StoreAppointmentRequest` (validates `doctor_id`, `service_id`, `slot_id`).

**Double-booking prevention** via DB transaction + pessimistic lock:
```php
DB::transaction(function () use ($request) {
    $slot = ScheduleSlot::where('id', $request->slot_id)
                        ->where('is_booked', false)
                        ->lockForUpdate()
                        ->firstOrFail();  // 404 if slot gone / already booked

    $slot->update(['is_booked' => true]);

    return Appointment::create([
        'patient_id' => $request->user()->id,
        'doctor_id'  => $request->doctor_id,
        'service_id' => $request->service_id,
        'slot_id'    => $slot->id,
        'status'     => AppointmentStatus::Pending,
    ]);
});
```
Returns `AppointmentResource` with 201.

### AppointmentController@destroy
Gate-checked by `AppointmentPolicy@delete` (patient owns record).
Only cancellable if `status` is `Pending` or `Confirmed` — otherwise 422.
Sets `status = Cancelled`, leaves record (soft-cancel, no hard delete).

### LoyaltyController@show
```
$request->user()->loyaltyAccount
→ LoyaltyResource
```
Fields: `points_balance`, `tier`
If no loyalty account exists (edge case), return `{ points_balance: 0, tier: null }`.

## Policy: AppointmentPolicy

```php
public function view(User $user, Appointment $appointment): bool
{
    return $user->id === $appointment->patient_id;
}

public function delete(User $user, Appointment $appointment): bool
{
    return $user->id === $appointment->patient_id;
}
```

Register in `AuthServiceProvider` (or via model-based auto-discovery since `Appointment` has a matching `AppointmentPolicy`).

## API Resource Shapes

```json
// DoctorResource
{ "id": 1, "name": "Dr. Smith", "specialization": "Orthodontics", "bio": "..." }

// SlotResource
{ "id": 5, "date": "2026-05-10", "start_time": "09:00", "end_time": "09:30" }

// ServiceResource
{ "id": 3, "name": "Cleaning", "description": "...", "duration_minutes": 30, "price": "49.00" }

// AppointmentResource
{
  "id": 12,
  "status": "pending",
  "doctor": { "id": 1, "name": "Dr. Smith" },
  "service": { "id": 3, "name": "Cleaning" },
  "slot": { "id": 5, "date": "2026-05-10", "start_time": "09:00" }
}

// LoyaltyResource
{ "points_balance": 120, "tier": "silver" }
```

## Validation

| Request | Rules |
|---------|-------|
| `RegisterRequest` | `name` required string max:255; `email` required email unique:users; `phone` nullable string max:20; `password` required confirmed Password::default() |
| `LoginRequest` | `email` required email; `password` required string |
| `StoreAppointmentRequest` | `doctor_id` required exists:doctors,id; `service_id` required exists:services,id; `slot_id` required exists:schedule_slots,id |

## Error Responses

All errors follow Laravel's default JSON exception format. Notable cases:
- Double-booking: `lockForUpdate()->firstOrFail()` returns 404 (slot gone)
- Non-patient login attempt: explicit 403 JSON response
- Cancel non-cancellable appointment: 422 with message
- Unauthorized appointment access/cancel: 403 via policy

## Testing Strategy

- Feature tests in `tests/Feature/Api/V1/`
- One test class per controller
- Use `Sanctum::actingAs($user)` for authenticated endpoints
- Test double-booking with two concurrent requests using a transaction assertion
- Test policy enforcement (patient A cannot cancel patient B's appointment)
