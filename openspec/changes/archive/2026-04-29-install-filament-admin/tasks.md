# Tasks: Install Filament Admin Panel

## Implementation Tasks

### T1 — Install Filament via Composer
- [x] Run `composer require filament/filament:"^4.0"` (v3 does not support Laravel 13; v4.11.1 installed)

---

### T2 — Scaffold the admin panel
- [x] Run `php artisan filament:install --panels` (panel ID: `admin`, non-interactive via pipe)
- [x] Confirm `app/Providers/Filament/AdminPanelProvider.php` was created

---

### T3 — Configure `AdminPanelProvider`
- [x] `->id('admin')` and `->path('admin')` scaffolded automatically
- [x] Added `->defaultThemeMode(ThemeMode::Dark)` (`Filament\Enums\ThemeMode`)
- [x] Implemented `FilamentUser` on `User` model with `canAccessPanel(Panel $panel): bool` returning `$this->hasRole('admin')` (Filament v4 requires this interface; panel-level closure not available)
- [x] `->login()` present

---

### T4 — Register `AdminPanelProvider` in `bootstrap/providers.php`
- [x] `filament:install` automatically registered `App\Providers\Filament\AdminPanelProvider::class` in `bootstrap/providers.php`

---

### T5 — Publish Filament assets
- [x] `filament:install` automatically published all CSS/JS/font assets to `public/js/filament/` and `public/css/filament/`

---

### T6 — Run tests to confirm no regressions
- [x] `php artisan test --compact` — 18 passed, 22 skipped, 0 failed (same as pre-install baseline)

---

### T7 — Verify panel loads and admin can log in
- [ ] Start the dev server: `php artisan serve`
- [ ] Navigate to `http://localhost:8000/admin` — confirm redirect to `/admin/login`
- [ ] Log in with `admin@example.com` / `changeme` — confirm access to the dashboard
- [ ] Attempt to log in with a patient account — confirm 403 or redirect back to login
