## Why

FR2 requires patients to manage their own profile information. Additionally, to prevent appointment booking abuse (spam bookings with unverified contact details), patients must have a verified email address and a verified phone number before any appointment can be created or requested.

Email verification infrastructure already exists — `User` implements `MustVerifyEmail`, `email_verified_at` column is present, and the `verification.verify` signed-URL route is registered — but the verification handler currently returns JSON. The link in the verification email opens in a browser, so the response must be a redirect back to the SPA. This change wires that redirect up and adds phone OTP verification on top, using the Vonage integration already installed in Change 23.

The profile page is the natural place to trigger and confirm both verifications, and it also exposes the notification channel preference introduced in Change 23 (which has no UI yet).

## What Changes

### 1. Profile API endpoints

- `GET /api/v1/profile` — returns `name`, `email`, `phone`, `notification_channel`, `email_verified_at` (nullable), `phone_verified_at` (nullable).
- `PATCH /api/v1/profile` — updates `name`, `phone`, `notification_channel`. If `phone` changes, clears `phone_verified_at` (phone must be re-verified after a number change).
- `PATCH /api/v1/profile/password` — separate endpoint; validates `current_password` via `Hash::check()`, applies new password.

### 2. Email verification — activate SPA redirect

`User` already implements `MustVerifyEmail`. The `verification.verify` named route is already registered. What changes:

- `AuthController::verifyEmail()` redirects to `/profile?verified=1` (instead of returning JSON) on both success and already-verified cases. Invalid/expired link redirects to `/profile?error=invalid_link`.
- New `POST /api/v1/email/verification-notification` endpoint (called by the SPA via Axios) — resends the verification email. Rate-limited to 1 request per minute per user. Returns JSON.

### 3. Phone verification — OTP via Vonage (or log in local)

New `phone_verifications` table:

```
id, user_id, code (6-digit string), expires_at, created_at
```

- `POST /api/v1/phone/send-otp` — generates a 6-digit code, stores it in `phone_verifications` (expires in 10 minutes), then:
  - `APP_ENV=local`: writes code to `laravel.log` only — no Vonage call, no credentials needed.
  - All other environments: sends via Vonage SMS (reuses `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM` from Change 23).
  - Rate-limited: max 3 OTP requests per 10 minutes per user.
- `POST /api/v1/phone/verify-otp` — validates that the code is correct, not expired, and belongs to the authenticated user; sets `phone_verified_at = now()`; deletes the used OTP row.

### 4. Migrations

- Add `phone_verified_at` (nullable timestamp) to `users` table, after `phone`.
- Create `phone_verifications` table: `id`, `user_id` (FK → `users`), `code` (string 6), `expires_at` (timestamp), `created_at` (timestamp only — no `updated_at`).

### 5. Booking gate

In `AppointmentController::store()` and `requestStore()`, before creating the appointment, guard:

```php
if (! $request->user()->hasVerifiedEmail() || ! $request->user()->phone_verified_at) {
    return response()->json([
        'message'          => 'El. paštas ir telefono numeris turi būti patvirtinti prieš rezervuojant vizitą.',
        'unverified_email' => ! $request->user()->hasVerifiedEmail(),
        'unverified_phone' => ! $request->user()->phone_verified_at,
    ], 403);
}
```

### 6. `ProfileView.vue` (SPA)

Single page at `/profile`, three sections:

- **Personal details** — name, phone (text inputs); email (read-only, with resend verification button if unverified, green checkmark if verified); notification preference radio (El. paštas / SMS / El. paštas ir SMS). Save → `PATCH /api/v1/profile`.
- **Phone verification** — shown only if `phone_verified_at` is null and `phone` is set. "Siųsti kodą" button → `POST /api/v1/phone/send-otp`; 6-digit OTP input + "Patvirtinti" button → `POST /api/v1/phone/verify-otp`; 10-minute countdown timer shown after code is sent; green checkmark + "Patvirtintas" once verified.
- **Password change** — current password, new password, confirm → `PATCH /api/v1/profile/password`.

### 7. Booking flow UX guard (SPA)

In `BookView.vue` and `RequestBookingView.vue`, on mount check `GET /api/v1/profile` for verification status. If either `email_verified_at` or `phone_verified_at` is null, show a banner: _"Norėdami rezervuoti vizitą, patvirtinkite el. paštą ir telefono numerį"_ with a link to `/profile`. The slot picker is hidden (or disabled) until both are verified.

## What Does NOT Change

- Registration flow — new users are unverified by default, which is correct.
- Admin and doctor Filament panels — untouched.
- Notification channel logic from Change 23 — untouched.
- Existing appointments for users already verified — unaffected (gate only applies to new bookings).

## Non-Goals

- Email address change (requires re-verification flow; out of scope).
- Admin-side verification override (admin can set `phone_verified_at` directly via Filament if needed, but no UI is added here).
- Two-factor authentication.
- Avatar upload.
