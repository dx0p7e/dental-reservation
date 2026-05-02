# Proposal: Booking API

## Summary

Add a Sanctum-authenticated REST API under `/api/v1/` that exposes the patient-facing booking flow to the Vue 3 SPA (Change 10). The API covers authentication, doctor/service discovery, slot availability, appointment management, and loyalty balance.

## Problem

The existing `api.php` routes only cover session-based auth used by the admin SPA. There is no patient-facing API for the public booking application. The Vue 3 SPA (Change 10) needs a versioned, token-authenticated API that patients can use without accessing the Filament admin panel.

## Goals

- Sanctum token-based auth for patients (register, login, logout)
- Doctor listing and per-doctor available slot lookup
- Service listing
- Patient's own appointment CRUD (create, list, cancel)
- Loyalty account summary (points balance + tier)
- Strict isolation: patients see only their own data; doctors/admins are blocked from these endpoints via policy

## Non-Goals

- Admin-facing API (covered by Filament)
- Doctor portal API (out of scope)
- Push notifications or webhooks
- Payment processing

## Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/auth/register` | — | Patient self-registration, assigns `patient` role |
| POST | `/api/v1/auth/login` | — | Returns Sanctum token |
| POST | `/api/v1/auth/logout` | sanctum | Revokes current token |
| GET | `/api/v1/doctors` | — | List active doctors with specialization |
| GET | `/api/v1/doctors/{doctor}/slots` | — | Non-booked slots for a doctor, filterable by `?date=` |
| GET | `/api/v1/services` | — | List all services (name, duration, price) |
| GET | `/api/v1/appointments` | sanctum | Authenticated patient's own appointments |
| POST | `/api/v1/appointments` | sanctum | Create booking; atomic slot lock via DB transaction |
| DELETE | `/api/v1/appointments/{appointment}` | sanctum | Cancel own appointment (pending/confirmed only) |
| GET | `/api/v1/loyalty` | sanctum | Patient's loyalty points balance and tier |

## Approach

- Versioned prefix `/api/v1/` added to `routes/api.php`
- New controller namespace `App\Http\Controllers\Api\V1\`
- Eloquent API Resources for consistent JSON shape
- Form Requests for validation
- `AppointmentPolicy` to enforce patient ownership
- DB transaction in `AppointmentController@store` to prevent double-booking (lock slot row with `lockForUpdate()`, check `is_booked`, mark booked atomically)
- Existing `AuthController` is session-based (Fortify); new `Api\V1\AuthController` will issue Sanctum tokens separately

## Dependencies

- Laravel Sanctum (already installed, v4)
- Spatie Permission (already installed) — `patient` role already exists
- All models already exist: `User`, `Doctor`, `ScheduleSlot`, `Service`, `Appointment`, `LoyaltyAccount`
