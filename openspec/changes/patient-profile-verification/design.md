## Context

**Existing app state relevant to this change:**

- `User` implements `MustVerifyEmail`; `email_verified_at` already exists on `users`; `verification.verify` named route registered at `GET /api/auth/email/verify/{id}/{hash}` (in `routes/api.php`, `signed` middleware). The `VerifyEmail` notification (Laravel built-in) uses this route for the link URL.
- `AuthController::verifyEmail()` currently returns JSON — must change to redirect for browser-based email link clicks.
- `AuthController::resendVerification()` exists at `POST /api/auth/email/resend` (rate-limited `throttle:6,1`). A new, independently rate-limited endpoint will be added under `/api/v1/`.
- `User` has `phone` (nullable string), `notification_channel` enum (Change 23), `Notifiable` trait, `HasApiTokens`, `HasRoles`.
- No `phone_verified_at` column exists yet; no `phone_verifications` table exists yet.
- No profile API endpoints exist under `/api/v1/`.
- `AppointmentController::store()` and `requestStore()` create appointments with no identity-verification guard.
- SPA lives at `resources/spa/`; views at `resources/spa/views/`; router at `resources/spa/router/index.ts`; API calls via `@spa/api/axios` (Axios with Sanctum CSRF/token).
- `laravel/vonage-notification-channel` already installed from Change 23; `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM` exist in `.env.example`.
- `AppointmentStatus` enum: `Pending`, `Confirmed`, `Cancelled`, `Completed`, `NoShow`.

## Goals / Non-Goals

**Goals:**
- Profile API (view + edit personal details, change password)
- Activate email verification SPA redirect
- Phone OTP via Vonage (log-only in local)
- Booking gate enforcing both verifications before appointment creation
- `ProfileView.vue` with all three sections
- Booking view UX guard banner

**Non-Goals:**
- Email address change
- Admin-side verification override UI
- Two-factor authentication
- Avatar upload
- In-app notifications bell

## Decisions

### 1 — Dedicated `ProfileController` under `Api\V1`, not extending `AuthController`

`AuthController` handles authentication (login, register, logout, verify). Profile management is a distinct concern. A separate `ProfileController` keeps the file focused and follows the existing V1 controller pattern.

### 2 — `phone_verified_at` cleared on phone number change

If a patient updates their phone number, `phone_verified_at` is set to `null`. The phone section in `ProfileView.vue` re-appears, prompting re-verification. This is the only safe behaviour — a new number must be re-verified independently.

### 3 — Phone OTP in `phone_verifications` table (not cache)

A dedicated table (with `user_id`, `code`, `expires_at`) survives server restarts and provides a clear audit trail. Old/expired rows are soft-deleted at lookup (deleted on successful verify or re-issue). Cache would require Redis/Memcached which are not guaranteed in all envs.

### 4 — Log OTP in local, send via Vonage in all other envs

`app()->environment('local')` check in `PhoneVerificationController::sendOtp()`. This avoids needing Vonage credentials locally. The same `$user->notify(new PhoneOtpNotification($code))` path is used for staging/production, which keeps the code path consistent and testable via `Notification::fake()`.

### 5 — `PhoneOtpNotification` — SMS-only notification class

Consistent with Change 23 patterns: a `Notification` class with `via()` returning `['vonage']` and a `toVonage()` method. The `ShouldQueue` interface is **not** implemented — OTP delivery must be synchronous (user is waiting for the code).

### 6 — `EmailVerificationController` for the new resend endpoint

A dedicated `App\Http\Controllers\Api\V1\EmailVerificationController` with a single `store()` method handles `POST /api/v1/email/verification-notification`. Rate-limited to `throttle:1,1` (1 per minute per IP). This keeps the resend action in the V1 namespace, distinct from the legacy `/api/auth/email/resend`.

### 7 — `AuthController::verifyEmail()` redirects to SPA

The signed verification URL is a browser link (not an XHR). Returning JSON is wrong for this flow. The method is updated to return `RedirectResponse`:
- Success / already-verified → `redirect(config('app.url').'/profile?verified=1')`
- Invalid hash → `redirect(config('app.url').'/profile?error=invalid_link')`

Return type changes from `JsonResponse` to `\Illuminate\Http\RedirectResponse`.

### 8 — Booking gate returns 403 with structured payload

The 403 body includes `unverified_email` and `unverified_phone` boolean flags. The SPA reads these flags to direct the patient to the correct profile section. HTTP 403 (Forbidden) is appropriate — the user is authenticated but not authorised to create the resource.

### 9 — Booking UX guard fetches `/api/v1/profile` on mount

`BookView.vue` and `RequestBookingView.vue` already call `onMounted()`. We extend those hooks to fetch the profile and check verification status. If unverified, a banner is shown and the action button is disabled. No separate state management store is introduced — a local `ref` per view is sufficient.

## Architecture

```
routes/api.php
├── GET  /api/v1/profile                         ProfileController@show
├── PATCH /api/v1/profile                        ProfileController@update
├── PATCH /api/v1/profile/password               ProfileController@updatePassword
├── POST /api/v1/email/verification-notification EmailVerificationController@store
├── POST /api/v1/phone/send-otp                  PhoneVerificationController@sendOtp
└── POST /api/v1/phone/verify-otp                PhoneVerificationController@verifyOtp

routes/api.php (existing, updated)
└── GET  /api/auth/email/verify/{id}/{hash}       AuthController@verifyEmail   → redirect to SPA

New files:
app/Http/Controllers/Api/V1/ProfileController.php
app/Http/Controllers/Api/V1/EmailVerificationController.php
app/Http/Controllers/Api/V1/PhoneVerificationController.php
app/Http/Requests/Api/V1/UpdateProfileRequest.php
app/Http/Requests/Api/V1/UpdatePasswordRequest.php
app/Notifications/PhoneOtpNotification.php
app/Models/PhoneVerification.php
database/migrations/*_add_phone_verified_at_to_users_table.php
database/migrations/*_create_phone_verifications_table.php
database/factories/PhoneVerificationFactory.php
resources/spa/views/ProfileView.vue
```

## Data Model

### `users` table additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `phone_verified_at` | `timestamp` | `null` | Nullable; set by `PhoneVerificationController::verifyOtp()` |

### `phone_verifications` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `user_id` | bigint FK | → `users.id`, cascades on delete |
| `code` | `varchar(6)` | 6-digit numeric string |
| `expires_at` | `timestamp` | 10 minutes from creation |
| `created_at` | `timestamp` | no `updated_at` |

## API Contracts

### `GET /api/v1/profile`

Response `200`:
```json
{
  "name": "Jonas Jonaitis",
  "email": "jonas@example.com",
  "phone": "+37060000000",
  "notification_channel": "email",
  "email_verified_at": "2026-04-01T10:00:00.000000Z",
  "phone_verified_at": null
}
```

### `PATCH /api/v1/profile`

Request body (all fields optional):
```json
{ "name": "string", "phone": "string|null", "notification_channel": "email|sms|both" }
```
Response `200`: same shape as `GET /api/v1/profile`.

### `PATCH /api/v1/profile/password`

Request body:
```json
{ "current_password": "string", "password": "string", "password_confirmation": "string" }
```
Response `200`: `{ "message": "Slaptažodis pakeistas." }`
Response `422`: validation errors including `current_password` mismatch.

### `POST /api/v1/email/verification-notification`

Response `200`: `{ "message": "Patvirtinimo nuoroda išsiųsta." }`
Response `200` (already verified): `{ "message": "El. paštas jau patvirtintas." }`
Response `429`: rate limit exceeded.

### `POST /api/v1/phone/send-otp`

Response `200`: `{ "message": "Kodas išsiųstas.", "expires_in_seconds": 600 }`
Response `422`: `{ "message": "Telefono numeris nenurodytas." }` (if `phone` is null)
Response `429`: rate limit exceeded (3/10min).

### `POST /api/v1/phone/verify-otp`

Request body: `{ "code": "123456" }`
Response `200`: `{ "message": "Telefonas patvirtintas.", "phone_verified_at": "..." }`
Response `422`: `{ "message": "Kodas neteisingas arba pasibaigė jo galiojimo laikas." }`

### `POST /api/v1/appointments` / `POST /api/v1/appointments/request` (gate)

Response `403` (when unverified):
```json
{
  "message": "El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant vizitą.",
  "unverified_email": true,
  "unverified_phone": false
}
```
