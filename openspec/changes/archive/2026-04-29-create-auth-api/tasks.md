# Tasks: Create Auth API

## Implementation Tasks

### T1 — Publish Sanctum and CORS configs
- [x] Run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` to create `config/sanctum.php`
- [x] Run `php artisan vendor:publish --tag="cors"` to create `config/cors.php`

---

### T2 — Update `config/sanctum.php`
- [x] Add `'localhost:5173'` to the `stateful` domains list (alongside existing `localhost`, `127.0.0.1:8000`, etc.)

---

### T3 — Update `config/cors.php`
- [x] Set `'paths'` to `['api/*', 'sanctum/csrf-cookie']`
- [x] Set `'allowed_origins'` to `[env('FRONTEND_URL', 'http://localhost:5173')]`
- [x] Set `'supports_credentials'` to `true`

---

### T4 — Add `FRONTEND_URL` to env files
- [x] Add `FRONTEND_URL=http://localhost:5173` to `.env`
- [x] Add `FRONTEND_URL=http://localhost:5173` to `.env.example`

---

### T5 — Update `config/fortify.php`
- [x] Set `'views' => false`
- [x] Set `'features'` to `[]` — remove all features (registration, resetPasswords, emailVerification, twoFactorAuthentication)
  - Note: `SendEmailVerificationNotification` listener is registered by Laravel's own `EventServiceProvider`, not by Fortify's feature flag — verification emails still work
  - Note: keeping `emailVerification` enabled would register a Fortify route also named `verification.verify`, colliding with ours

---

### T6 — Update `FortifyServiceProvider`
- [x] Remove the `configureViews()` private method and its call from `boot()`
- [x] Remove unused `Inertia` import

---

### T7 — Register Sanctum middleware in `bootstrap/app.php`
- [x] Add `api` route file: `api: __DIR__.'/../routes/api.php'` to `withRouting()`
- [x] Add `EnsureFrontendRequestsAreStateful::class` prepended to the `api` middleware group

---

### T8 — Create `routes/api.php`
- [x] Create `routes/api.php` with the six `auth` routes as specified in design
- [x] Apply correct middleware per route: `auth:sanctum`, `signed`, `throttle:6,1`
- [x] Import `AuthController` and use `Route::prefix('auth')`

---

### T9 — Create `AuthController`
- [x] Create `app/Http/Controllers/AuthController.php`
- [x] Implement `register()`: validate, force `role = 'patient'`, create user, fire `Registered` event, return `201`
- [x] Implement `login()`: validate, `Auth::attempt()`, regenerate session, return `200` with user or `422`
- [x] Implement `logout()`: logout, invalidate session, regenerate token, return `200`
- [x] Implement `user()`: return authenticated user `only('id', 'name', 'email', 'role')`
- [x] Implement `verifyEmail()`: find user by id, verify hash, mark verified, fire `Verified` event, return `200`
- [x] Implement `resendVerification()`: check already-verified guard, send notification, return `200`

---

### T10 — Verify
- [x] Run `php artisan route:list --path=api/auth` and confirm all 6 routes appear with correct middleware
- [x] Run `php artisan migrate:fresh --seed` to confirm no regressions
- [x] Run `php artisan test` to confirm all existing tests still pass
- [ ] Manually test register endpoint with HTTP client (curl / Postman): confirm `201`, role forced to `patient`, verification email queued
- [ ] Manually test login endpoint: confirm `200` + Set-Cookie, `422` on bad credentials
