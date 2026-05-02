# Design: Create Auth API

## Overview

One new controller (`AuthController`), one new API route file, and three config updates. Sanctum and its dependencies are already available via the Laravel scaffold — only `sanctum.php` and `cors.php` need to be published. Fortify is already installed and configured.

---

## Prerequisites / Package State

- `laravel/sanctum` — already in `composer.json` (Laravel scaffold); config not yet published
- `laravel/fortify` — already installed; config exists at `config/fortify.php`
- `fruitcake/laravel-cors` / `illuminate/http` CORS — Laravel 13 uses built-in CORS middleware; `cors.php` is published separately

---

## Route Registration

Add to `bootstrap/app.php` `withRouting()`:

```php
api: __DIR__.'/../routes/api.php',
```

Create `routes/api.php`:

```php
Route::prefix('auth')->group(function () {
    Route::post('register',        [AuthController::class, 'register']);
    Route::post('login',           [AuthController::class, 'login']);
    Route::post('logout',          [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('user',             [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
         ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/resend',    [AuthController::class, 'resendVerification'])
         ->middleware(['auth:sanctum', 'throttle:6,1']);
});
```

---

## `AuthController` (`app/Http/Controllers/AuthController.php`)

### `register(Request $request): JsonResponse`

- Validate: `name` (required, string, max:255), `email` (required, email, unique:users), `password` (required, confirmed, Password::default())
- Create user: `User::create([..., 'role' => 'patient'])` — role is **always** forced to `'patient'`, never taken from input
- Fire `Illuminate\Auth\Events\Registered` — triggers email verification mail via Fortify/Laravel listener
- Return `201` with `['user' => $user->only('id', 'name', 'email', 'role')]`
- Validation failure → Laravel auto-returns `422` JSON

### `login(Request $request): JsonResponse`

- Validate: `email` (required, email), `password` (required, string)
- Attempt auth via `Auth::attempt(['email' => $request->email, 'password' => $request->password])`
- On failure: return `422` with `['message' => 'The provided credentials are incorrect.']`
- On success: `$request->session()->regenerate()`, return `200` with `['user' => $user->only('id', 'name', 'email', 'role')]`

### `logout(Request $request): JsonResponse`

- `Auth::guard('web')->logout()`
- `$request->session()->invalidate()`
- `$request->session()->regenerateToken()`
- Return `200` with `['message' => 'Logged out.']`

### `user(Request $request): JsonResponse`

- Return `200` with `['user' => $request->user()->only('id', 'name', 'email', 'role')]`

### `verifyEmail(Request $request, int $id, string $hash): JsonResponse`

- Retrieve user: `User::findOrFail($id)`
- Verify hash: `hash_equals(sha1($user->getEmailForVerification()), $hash)` — if mismatch return `403`
- If already verified: return `200` with `['message' => 'Already verified.']`
- Call `$user->markEmailAsVerified()`, fire `Illuminate\Auth\Events\Verified`
- Return `200` with `['message' => 'Email verified.']`

> The `signed` middleware on the route validates the URL signature automatically before the controller runs. The controller only needs to check the hash matches the user.

### `resendVerification(Request $request): JsonResponse`

- If `$request->user()->hasVerifiedEmail()` → return `200` with `['message' => 'Already verified.']`
- Call `$request->user()->sendEmailVerificationNotification()`
- Return `200` with `['message' => 'Verification link sent.']`

---

## Config: `config/sanctum.php`

Publish with:
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Update `stateful` domains to include the Vite dev server:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', implode(',', [
    'localhost',
    'localhost:3000',
    'localhost:5173',
    '127.0.0.1',
    '127.0.0.1:8000',
    '::1',
    Str::of(config('app.url'))->replaceFirst('https://', '')->replaceFirst('http://', ''),
]))),
```

---

## Config: `config/cors.php`

Publish with:
```bash
php artisan vendor:publish --tag="cors"
```

Key settings:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

Also add to `.env` / `.env.example`:
```
FRONTEND_URL=http://localhost:5173
```

---

## Config: `config/fortify.php`

### Disable Fortify's route registration

Set `'views' => false` to stop Fortify from registering its own view routes.

### Features — disable all

```php
'features' => [],
```

Remove all features: `Features::registration()`, `Features::resetPasswords()`, `Features::emailVerification()`, `Features::twoFactorAuthentication(...)`.

> `emailVerification` does **not** need to be listed here to make verification emails work. The `SendEmailVerificationNotification` listener that responds to the `Registered` event is registered by Laravel's own `Illuminate\Foundation\Support\Providers\EventServiceProvider::configureEmailVerification()` — unconditionally on every boot, independent of any Fortify feature flag. Keeping `emailVerification` enabled would cause Fortify to register its own `/email/verify/{id}/{hash}` route (also named `verification.verify`), which would collide with ours in `routes/api.php`. Empty features = no Fortify routes at all.

### Middleware — leave unchanged

Leave `'middleware' => ['web']` as-is. With an empty features array, Fortify registers no routes and this setting has no effect on our API.

---

## `bootstrap/app.php` — Sanctum middleware

Add `EnsureFrontendRequestsAreStateful` to the `api` middleware group:

```php
->withMiddleware(function (Middleware $middleware): void {
    // existing web middleware...
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

---

## `FortifyServiceProvider` — remove view registrations

The `configureViews()` method in `FortifyServiceProvider` currently registers Inertia views for login, register, forgot-password, etc. These are no longer needed (the API handles auth). Remove `configureViews()` and its call in `boot()` to avoid loading Inertia for API requests. Keep `configureActions()` (for `CreateNewUser`) and `configureRateLimiting()`.

> `CreateNewUser` can remain as-is — it will no longer be called by Fortify's own routes, only if we choose to reuse it. The `AuthController` creates users directly via `User::create()` to maintain explicit control over the `role` field.

---

## JSON Response Summary

| Endpoint | Success | Failure |
|---|---|---|
| `POST /register` | `201 {user}` | `422 {errors}` |
| `POST /login` | `200 {user}` | `422 {message}` |
| `POST /logout` | `200 {message}` | `401` (unauthenticated) |
| `GET /user` | `200 {user}` | `401` (unauthenticated) |
| `GET /email/verify/{id}/{hash}` | `200 {message}` | `403` (bad hash), `429` (throttle) |
| `POST /email/resend` | `200 {message}` | `401`, `429` (throttle) |
