# Proposal: Create Auth API

## What

Implement a JSON authentication API for patient and doctor users using Laravel Sanctum (SPA/cookie mode) and Laravel Fortify. Six endpoints are exposed under `/api/auth/`:

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | — | Patient self-registration |
| `POST` | `/api/auth/login` | — | Issue Sanctum session cookie |
| `POST` | `/api/auth/logout` | `auth:sanctum` | Invalidate session |
| `GET` | `/api/auth/user` | `auth:sanctum` | Return authenticated user + role |
| `GET` | `/api/auth/email/verify/{id}/{hash}` | signed, throttle | Verify email address |
| `POST` | `/api/auth/email/resend` | `auth:sanctum`, throttle | Resend verification email |

A single `AuthController` handles all six endpoints. All responses are JSON — no Blade views involved.

Three config files are updated to enable Sanctum SPA mode and trim Fortify to only the features this change needs:
- `config/sanctum.php` — stateful domain for `localhost:5173` (Vite dev server)
- `config/cors.php` — `supports_credentials: true`, paths include `api/*` and `sanctum/csrf-cookie`
- `config/fortify.php` — disable all Fortify routes; keep only `emailVerification` in `features`

## Why

The Vue 3 SPA (served by Vite at `localhost:5173`) needs a secure, cookie-based auth layer:

- **Sanctum SPA mode** is the correct choice: it issues `HttpOnly` session cookies instead of tokens, avoids XSS token theft, and integrates naturally with Laravel's session + CSRF system.
- **A single AuthController** over Fortify's built-in routes keeps the API surface explicit and JSON-first. Fortify's own routes (`/login`, `/register`, etc.) are designed for redirects and Blade views — disabling them prevents duplicate routes and redirect responses leaking into the API.
- **Patient-only self-registration** enforces the role at the controller level (never from user input), satisfying the business rule that doctors and admins cannot self-register.
- **Email verification** is required by `MustVerifyEmail` on the `User` model and must be handled before patients can book appointments.

## Non-goals

- Admin authentication — handled by Filament (separate change)
- 2FA endpoints — separate change
- Password reset endpoints — separate change
- Authorization policies / gates — separate change
- Doctor registration / onboarding — separate change
