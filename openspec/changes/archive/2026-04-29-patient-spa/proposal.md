# Proposal: Patient SPA

## Summary

Build a patient-facing Vue 3 SPA that consumes the `/api/v1/` booking API introduced in Change 9. The SPA lives in `patient-spa/` at the project root, has its own Vite configuration, and runs as a standalone single-page application on `localhost:5173` during development.

## Problem

The booking API (Change 9) exposes all the endpoints patients need, but there is no frontend through which patients can discover doctors, pick a slot, make a booking, or review their appointments. The existing Laravel frontend is Inertia-driven and serves the admin panel — it is not the right home for a public-facing booking experience.

## Goals

- Public landing page showing available doctors and services (no auth required)
- Patient self-registration and login backed by Change 9 Sanctum token API
- Slot picker for a chosen doctor with optional date filter
- Booking confirmation screen
- Authenticated appointment list with status badges
- Loyalty balance view
- Client-side auth guard redirecting unauthenticated visitors away from protected pages
- Token stored in `localStorage`, sent as `Authorization: Bearer {token}` on every API request

## Non-Goals

- Admin or doctor portal views (covered by Filament)
- Server-side rendering
- Payment processing
- Push notifications
- Native mobile build

## Views / Routes

| Path | Auth | Description |
|------|------|-------------|
| `/` | — | Landing page — doctor cards + service list |
| `/login` | — | Login form → redirect to `/appointments` |
| `/register` | — | Registration form → redirect to `/appointments` |
| `/doctors/:id/slots` | required | Slot picker for a doctor, filterable by `?date=` |
| `/book` | required | Booking confirmation (doctor, service, slot pre-filled) |
| `/appointments` | required | Patient's own appointments with status badges |
| `/loyalty` | required | Points balance and current tier |

## Approach

- **Framework**: Vue 3 + Vite, scaffolded at `patient-spa/` (separate from the Laravel resources directory)
- **Routing**: Vue Router 4 — `createWebHistory`, `beforeEach` auth guard
- **State**: Pinia — `authStore` (user, token, login, logout) and `bookingStore` (selected doctor, slot, service)
- **HTTP**: Axios with a shared instance; `Authorization: Bearer` interceptor reads token from `authStore`
- **Styles**: Tailwind CSS v3 (standalone PostCSS config in `patient-spa/`; avoids coupling to the main app's Tailwind v4 Vite plugin)
- **No component library** — plain Tailwind utility classes throughout, consistent with thesis scope

## API Dependency

All API calls target `http://localhost:8000/api/v1/`. CORS and Sanctum stateful domains are already configured for `localhost:5173` (Change 7 / Change 9).
