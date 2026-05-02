# Proposal: Install Filament Admin Panel

## What

Install and configure **Filament v3** as the administration panel for the dental reservation system. The panel is mounted at `/admin`, uses Filament's built-in authentication (separate from the Sanctum API), and is restricted to users who hold the `admin` role via `spatie/laravel-permission`.

This change covers installation and access control only. No Filament Resources (Doctor, Appointment, etc.) are created here — those are separate changes.

## Why

The system needs an admin interface for managing records (appointments, doctors, services, loyalty rules, etc.). Filament v3 is the de-facto Laravel admin framework: it generates fully-featured CRUD resources from Eloquent models, ships with a polished UI, and integrates directly with the existing `User` model and `spatie/laravel-permission` role system.

Keeping admin authentication entirely within Filament (separate from the Sanctum SPA API used by patients and doctors) provides clean separation of concerns:

- **Patients and doctors** authenticate via `POST /api/auth/login` → Sanctum SPA cookie
- **Admins** authenticate via Filament's `/admin/login` → Filament session

This separation means no cross-contamination between the SPA auth layer and the admin panel, and no need to implement admin-specific API guards or token flows.

## Scope

**Included:**
- `filament/filament:^3.0` installed via Composer
- `AdminPanelProvider` generated and configured: path `/admin`, id `admin`, default dark mode
- Access gate: only users where `hasRole('admin')` returns true may access the panel
- `AdminPanelProvider` registered in `bootstrap/providers.php`
- Verified: seeded admin account (`admin@example.com`) can reach and log into `/admin`

**Not included:**
- Filament Resources (Doctors, Appointments, Services, etc.) — separate changes
- Admin-specific 2FA — separate change
- Custom Filament theme — separate change
