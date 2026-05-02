# Tasks: Patient Profile Page + Email & Phone Verification + Booking Gate

## 1. Migrations

- [x] 1.1 Run `php artisan make:migration add_phone_verified_at_to_users_table --no-interaction` and add `$table->timestamp('phone_verified_at')->nullable()->after('phone')` in `up()` and `$table->dropColumn('phone_verified_at')` in `down()`
- [x] 1.2 Run `php artisan make:migration create_phone_verifications_table --no-interaction` and define: `$table->id()`, `$table->foreignId('user_id')->constrained()->cascadeOnDelete()`, `$table->string('code', 6)`, `$table->timestamp('expires_at')`, `$table->timestamp('created_at')` — no `updated_at`, no `timestamps()`. In `down()` call `Schema::dropIfExists('phone_verifications')`.
- [x] 1.3 Run `php artisan migrate --no-interaction`

## 2. Model Updates

- [x] 2.1 In `app/Models/User.php`, add `'phone_verified_at' => 'datetime'` to the `casts()` array
- [x] 2.2 In `app/Models/User.php`, add a `hasVerifiedPhone(): bool` method: `return (bool) $this->phone_verified_at;`
- [x] 2.3 Create `app/Models/PhoneVerification.php` — `$fillable = ['user_id', 'code', 'expires_at', 'created_at']`; set `public $timestamps = false` (disables Eloquent's automatic timestamp handling entirely — `created_at` is instead passed explicitly in every `create()` call). Add a `user()` `belongsTo` relationship. Add a scope `scopeValid(Builder $query): void` that filters `expires_at > now()`.
- [x] 2.4 Run `php artisan make:factory PhoneVerificationFactory --model=PhoneVerification --no-interaction` and define: `user_id` → `User::factory()`, `code` → `str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`, `expires_at` → `now()->addMinutes(10)`, `created_at` → `now()`

## 3. Profile Controller + Form Requests

- [x] 3.1 Run `php artisan make:request Api/V1/UpdateProfileRequest --no-interaction`. Rules: `name` → `['sometimes', 'string', 'max:255']`, `phone` → `['sometimes', 'nullable', 'string', 'max:20']`, `notification_channel` → `['sometimes', 'in:email,sms,both']`. `authorize()` returns `true`.
- [x] 3.2 Run `php artisan make:request Api/V1/UpdatePasswordRequest --no-interaction`. Rules: `current_password` → `['required', 'string']`, `password` → `['required', 'string', 'confirmed', Password::default()]`. `authorize()` returns `true`. Add `use Illuminate\Validation\Rules\Password;` import.
- [x] 3.3 Run `php artisan make:controller Api/V1/ProfileController --no-interaction` and implement three methods:
  - `show(Request $request): JsonResponse` — returns `$request->user()->only(['name', 'email', 'phone', 'notification_channel', 'email_verified_at', 'phone_verified_at'])`
  - `update(UpdateProfileRequest $request): JsonResponse` — detect phone change: `$phoneChanged = $request->has('phone') && $request->phone !== $request->user()->phone`; fill `name`, `phone`, `notification_channel` from request (only present fields); if `$phoneChanged`, set `phone_verified_at = null`; save and return updated profile fields
  - `updatePassword(UpdatePasswordRequest $request): JsonResponse` — check `Hash::check($request->current_password, $request->user()->password)`; if mismatch return `422` with message; otherwise `$request->user()->update(['password' => $request->password])`

## 4. Email Verification Flow

- [x] 4.1 Run `php artisan make:controller Api/V1/EmailVerificationController --no-interaction` and implement `store(Request $request): JsonResponse`:
  - If `$request->user()->hasVerifiedEmail()`, return `200` with `{ "message": "El. paštas jau patvirtintas." }`
  - Otherwise call `$request->user()->sendEmailVerificationNotification()` and return `200` with `{ "message": "Patvirtinimo nuoroda išsiųsta." }`
- [x] 4.2 In `app/Http/Controllers/AuthController.php`, update `verifyEmail()` to return `RedirectResponse` instead of `JsonResponse`:
  - Change return type annotation to `\Illuminate\Http\RedirectResponse`
  - Add `use Illuminate\Http\RedirectResponse;` import
  - Replace all three `return response()->json(...)` calls with redirects:
    - Invalid hash → `return redirect(config('app.url').'/profile?error=invalid_link')`
    - Already verified → `return redirect(config('app.url').'/profile?verified=1')`
    - Success (after `markEmailAsVerified()` + `event(new Verified($user))`) → `return redirect(config('app.url').'/profile?verified=1')`

## 5. Phone OTP Notification

- [x] 5.1 Run `php artisan make:notification PhoneOtpNotification --no-interaction` and implement:
  - Constructor: `public function __construct(public readonly string $code) {}`
  - `via()` returns `['vonage']`
  - `toVonage()` returns `(new VonageMessage)->content("Jūsų patvirtinimo kodas: {$this->code}. Galioja 10 min.")` — add `use Illuminate\Notifications\Messages\VonageMessage;` import
  - Remove `toMail()` and `toArray()` stubs
  - Does **not** implement `ShouldQueue` — OTP must be delivered synchronously

## 6. Phone Verification Controller

- [x] 6.1 Run `php artisan make:controller Api/V1/PhoneVerificationController --no-interaction` and implement two methods:
  - `sendOtp(Request $request): JsonResponse`:
    - `$user = $request->user()`
    - Guard: if `$user->phone` is null, return `422` with `{ "message": "Telefono numeris nenurodytas." }`
    - Rate limit: `if (RateLimiter::tooManyAttempts('otp:'.$user->id, 3)) { return response()->json(['message' => 'Per daug bandymų. Bandykite vėliau.'], 429); }`; otherwise `RateLimiter::hit('otp:'.$user->id, 600)`
    - Generate code: `$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`
    - Delete any existing OTP for user: `PhoneVerification::where('user_id', $user->id)->delete()`
    - Create: `PhoneVerification::create(['user_id' => $user->id, 'code' => $code, 'expires_at' => now()->addMinutes(10), 'created_at' => now()])`
    - Deliver: `if (app()->environment(['local', 'testing'])) { Log::info("OTP for user {$user->id}: {$code}"); } else { $user->notify(new PhoneOtpNotification($code)); }` — both `local` and `testing` environments use the log-only path; Vonage is only called in staging/production
    - Return `200`: `{ "message": "Kodas išsiųstas.", "expires_in_seconds": 600 }`
  - `verifyOtp(Request $request): JsonResponse`:
    - Validate: `$request->validate(['code' => ['required', 'string', 'size:6']])`
    - `$user = $request->user()`
    - Find: `$otp = PhoneVerification::where('user_id', $user->id)->where('code', $request->code)->where('expires_at', '>', now())->first()`
    - If not found: return `422` with `{ "message": "Kodas neteisingas arba pasibaigė jo galiojimo laikas." }`
    - `$user->update(['phone_verified_at' => now()])`
    - `$otp->delete()`
    - Return `200`: `{ "message": "Telefonas patvirtintas.", "phone_verified_at": $user->fresh()->phone_verified_at }`
- [x] 6.2 Add required imports to `PhoneVerificationController`: `use App\Models\PhoneVerification;`, `use App\Notifications\PhoneOtpNotification;`, `use Illuminate\Support\Facades\Log;`, `use Illuminate\Support\Facades\RateLimiter;`

## 7. Routes

- [x] 7.1 In `routes/api.php`, inside the `auth:sanctum` middleware group under `v1`, add:
  ```php
  Route::get('profile', [V1ProfileController::class, 'show'])->name('profile.show');
  Route::patch('profile', [V1ProfileController::class, 'update'])->name('profile.update');
  Route::patch('profile/password', [V1ProfileController::class, 'updatePassword'])->name('profile.password');
  Route::post('email/verification-notification', [V1EmailVerificationController::class, 'store'])->middleware('throttle:1,1')->name('email.verification.send');
  Route::post('phone/send-otp', [V1PhoneVerificationController::class, 'sendOtp'])->name('phone.send-otp');
  Route::post('phone/verify-otp', [V1PhoneVerificationController::class, 'verifyOtp'])->name('phone.verify-otp');
  ```
- [x] 7.2 Add imports to `routes/api.php`: `use App\Http\Controllers\Api\V1\ProfileController as V1ProfileController;`, `use App\Http\Controllers\Api\V1\EmailVerificationController as V1EmailVerificationController;`, `use App\Http\Controllers\Api\V1\PhoneVerificationController as V1PhoneVerificationController;`

## 8. Booking Gate

- [x] 8.1 In `AppointmentController::store()`, add guard **before** the `DB::transaction(...)` block:
  ```php
  if (! $request->user()->hasVerifiedEmail() || ! $request->user()->phone_verified_at) {
      return response()->json([
          'message'          => 'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant vizitą.',
          'unverified_email' => ! $request->user()->hasVerifiedEmail(),
          'unverified_phone' => ! $request->user()->phone_verified_at,
      ], 403);
  }
  ```
- [x] 8.2 In `AppointmentController::requestStore()`, add the same guard **before** `Appointment::create(...)`. The method currently has no transaction, so place it at the very top of the method body.

## 9. SPA — `ProfileView.vue`

- [x] 9.1 Create `resources/spa/views/ProfileView.vue` with three sections:
  - **Personal details section**: `name` text input, `email` read-only input with inline resend button (shown when `email_verified_at` is null) or green "✓ Patvirtintas" badge; `phone` text input; `notification_channel` radio group (El. paštas / SMS / El. paštas ir SMS); "Išsaugoti" button → `PATCH /api/v1/profile`. Show success/error messages.
  - **Phone verification section**: visible only when `phone` is set and `phone_verified_at` is null. "Siųsti kodą" button → `POST /api/v1/phone/send-otp`; 6-character OTP `<input>` + "Patvirtinti" button → `POST /api/v1/phone/verify-otp`; countdown timer (counts down from 600 seconds, displayed as `MM:SS`); green badge + "Patvirtintas" once `phone_verified_at` is set. Timer resets on each `send-otp` call.
  - **Password change section**: `current_password`, `password`, `password_confirmation` inputs; "Keisti slaptažodį" button → `PATCH /api/v1/profile/password`. Show success/error messages.
  - On mount, read `?verified=1` query param and display a success notice ("El. paštas patvirtintas!") if present.
  - On mount, read `?error=invalid_link` and display an error notice if present.
- [x] 9.2 Add `/profile` route to `resources/spa/router/index.ts`: `{ path: '/profile', component: () => import('@spa/views/ProfileView.vue'), meta: { requiresAuth: true } }`
- [x] 9.3 Add a "Mano profilis" link to `AppNavbar.vue` (or equivalent nav component) pointing to `/profile` — visible only when authenticated

## 10. SPA — Booking UX Guard

- [x] 10.1 In `BookView.vue`, add to `onMounted()`: fetch `GET /api/v1/profile`; store `profileVerified = ref(false)`; set to `true` only when both `email_verified_at` and `phone_verified_at` are non-null. Show a banner `"Norėdami rezervuoti vizitą, patvirtinkite el. paštą ir telefono numerį"` with a `<RouterLink to="/profile">` link when `!profileVerified`. Disable the "Rezervuoti" button when `!profileVerified`.
- [x] 10.2 Apply the same guard pattern to `RequestBookingView.vue`

## 11. Tests

- [ ] 11.1 Run `php artisan make:test --pest PatientProfileTest --no-interaction`
- [ ] 11.2 Test: `GET /api/v1/profile` returns correct fields for authenticated user
- [ ] 11.3 Test: `PATCH /api/v1/profile` updates `name`, `phone`, `notification_channel`
- [ ] 11.4 Test: `PATCH /api/v1/profile` clears `phone_verified_at` when `phone` changes
- [ ] 11.5 Test: `PATCH /api/v1/profile` does **not** clear `phone_verified_at` when only `name` changes
- [ ] 11.6 Test: `PATCH /api/v1/profile/password` rejects wrong `current_password` with 422
- [ ] 11.7 Test: `PATCH /api/v1/profile/password` updates password when `current_password` is correct
- [ ] 11.8 Test: `POST /api/v1/email/verification-notification` sends notification when email is unverified
- [ ] 11.9 Test: `POST /api/v1/email/verification-notification` returns 200 (already verified) without dispatching
- [ ] 11.10 Test: `POST /api/v1/phone/send-otp` returns 422 when `phone` is null
- [ ] 11.11 Test: `POST /api/v1/phone/send-otp` creates a `PhoneVerification` row and dispatches no notification (tests run under `APP_ENV=testing`, which hits the log-only branch — assert `Notification::assertNothingSent()` and that a `PhoneVerification` row exists in the database)
- [ ] 11.12 Test: `POST /api/v1/phone/verify-otp` with valid code sets `phone_verified_at` and deletes OTP row
- [ ] 11.13 Test: `POST /api/v1/phone/verify-otp` with expired code returns 422
- [ ] 11.14 Test: `POST /api/v1/phone/verify-otp` with wrong code returns 422
- [ ] 11.15 Test: `POST /api/v1/appointments` returns 403 when email is unverified
- [ ] 11.16 Test: `POST /api/v1/appointments` returns 403 when phone is unverified
- [ ] 11.17 Test: `POST /api/v1/appointments` proceeds when both email and phone are verified
- [ ] 11.18 Test: `POST /api/v1/appointments/request` returns 403 when either verification is missing
- [ ] 11.19 Test: `GET /api/auth/email/verify/{id}/{hash}` redirects to `/profile?verified=1` on success
- [ ] 11.20 Test: `GET /api/auth/email/verify/{id}/{hash}` redirects to `/profile?error=invalid_link` on bad hash

## 12. Code Style

- [ ] 12.1 Run `vendor/bin/pint app/Models/User.php app/Models/PhoneVerification.php app/Http/Controllers/AuthController.php app/Http/Controllers/Api/V1/ProfileController.php app/Http/Controllers/Api/V1/EmailVerificationController.php app/Http/Controllers/Api/V1/PhoneVerificationController.php app/Http/Controllers/Api/V1/AppointmentController.php app/Http/Requests/Api/V1/UpdateProfileRequest.php app/Http/Requests/Api/V1/UpdatePasswordRequest.php app/Notifications/PhoneOtpNotification.php tests/Feature/PatientProfileTest.php --format agent`
