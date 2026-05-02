# Design: Install Filament Admin Panel

## Overview

One Composer install, one Artisan scaffold command, and targeted configuration changes. No new migrations, no new models. The existing `User` model (with `HasRoles` from `spatie/laravel-permission`) is used as-is.

---

## Installation

```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
```

The `filament:install --panels` command generates `app/Providers/Filament/AdminPanelProvider.php` and registers it automatically in `bootstrap/providers.php`.

> If the command prompts for a panel ID, answer `admin`.

---

## `AdminPanelProvider` Configuration

File: `app/Providers/Filament/AdminPanelProvider.php`

### Panel basics

```php
->id('admin')
->path('admin')
->defaultThemeMode(DefaultThemeMode::Dark)
```

### Authentication

Filament's built-in authentication is used unchanged — it handles login/logout at `/admin/login` with its own session, completely separate from the Sanctum SPA session. No extra configuration is needed.

### Access gate — `canAccess()`

Override `canAccess()` to restrict the panel to users with the `admin` role:

```php
->authMiddleware([
    Authenticate::class,
])
->canAccessPanel(fn (Model $user): bool => $user->hasRole('admin'))
```

`canAccessPanel` is the Filament v3 hook that controls who may enter the panel after authentication. Any authenticated user who fails this check receives a 403 response. `$user->hasRole('admin')` delegates to `spatie/laravel-permission`, which checks the `model_has_roles` pivot table.

### Complete `AdminPanelProvider::panel()` method

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->defaultThemeMode(DefaultThemeMode::Dark)
        ->colors([
            'primary' => Color::Amber,
        ])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->pages([
            Pages\Dashboard::class,
        ])
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->widgets([
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ])
        ->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->authMiddleware([
            Authenticate::class,
        ])
        ->canAccessPanel(fn (Model $user): bool => $user->hasRole('admin'));
}
```

---

## Provider Registration

Filament's install command adds `AdminPanelProvider` to `bootstrap/providers.php` automatically. After the command runs, `providers.php` will contain:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
```

If the install command does not add it (e.g., because it was already present or skipped), add it manually.

---

## Filament Assets

After installation, publish Filament's assets so the panel UI loads correctly:

```bash
php artisan filament:assets
```

Or run `npm run build` if Vite is used for Filament assets.

---

## User Model — No Changes Needed

`User` already has:
- `HasRoles` trait from `spatie/laravel-permission`
- `isAdmin(): bool` helper (`$this->role === 'admin'`)

Filament requires the panel user model to implement `FilamentUser` contract if custom access logic is needed — but since we use `canAccessPanel` on the panel itself, the `User` model does **not** need to implement `FilamentUser`. The gate closure on the panel is sufficient.

---

## Auth Separation Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  Dental Reservation — Auth Architecture                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Vue SPA (localhost:5173)                                   │
│    │                                                        │
│    ├─ POST /api/auth/login  ──→  Sanctum SPA session        │
│    ├─ GET  /api/auth/user   ──→  auth:sanctum middleware     │
│    └─ (patients + doctors)                                  │
│                                                             │
│  Filament Admin (localhost:8000/admin)                      │
│    │                                                        │
│    ├─ GET  /admin/login     ──→  Filament login page        │
│    ├─ POST /admin/login     ──→  Filament session           │
│    └─ canAccessPanel() ──→  hasRole('admin') check         │
│                                                             │
│  No shared session, no shared guard, no token overlap       │
└─────────────────────────────────────────────────────────────┘
```

---

## Seeded Admin Account

The `AdminUserSeeder` created in the previous change seeds:
- Email: `admin@example.com` (from `ADMIN_EMAIL` env var)
- Password: `changeme` (from `ADMIN_PASSWORD` env var)
- Role: `admin` (assigned via `spatie/laravel-permission`)

This account should be used for login verification in T7.
