# Tasks: Create Eloquent Models

## Implementation Tasks

### T1 — Install packages ✅
- [x] Run `composer require spatie/laravel-permission`
- [x] Run `composer require spatie/laravel-activitylog`
- [x] Run `php artisan vendor:publish --all` (published migrations + config for both packages)
- [x] Deleted duplicate `2026_04_28_214137_add_two_factor_columns_to_users_table.php` (conflict with existing Fortify migration)
- [x] Run `php artisan migrate` to apply the new package migrations

---

### T2 — Update `User` model ✅
- [x] Remove PHP attribute annotations `#[Fillable]` and `#[Hidden]` from `app/Models/User.php`
- [x] Add `implements MustVerifyEmail` to the class declaration
- [x] Import `Illuminate\Contracts\Auth\MustVerifyEmail`
- [x] Import and use `Spatie\Permission\Traits\HasRoles`
- [x] Replace with `$fillable = ['name', 'email', 'password', 'phone', 'role']`
- [x] Add `$hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token']`
- [x] Add casts for `gdpr_consent_at` and `deletion_requested_at` as `datetime`
- [x] Add `isPatient()`, `isDoctor()`, `isAdmin()` role helper methods
- [x] Add relationships: `doctor()`, `appointments()`, `loyaltyAccount()`

---

### T3 — Create `Doctor` model ✅
- [x] Created `app/Models/Doctor.php`

---

### T4 — Create `Service` model ✅
- [x] Created `app/Models/Service.php`

---

### T5 — Create `DoctorSchedule` model ✅
- [x] Created `app/Models/DoctorSchedule.php`

---

### T6 — Create `ScheduleSlot` model ✅
- [x] Created `app/Models/ScheduleSlot.php`

---

### T7 — Create `Appointment` model ✅
- [x] Created `app/Enums/AppointmentStatus.php`
- [x] Created `app/Models/Appointment.php` with `LogsActivity`

---

### T8 — Create `LoyaltyTier` model ✅
- [x] Created `app/Models/LoyaltyTier.php`

---

### T9 — Create `LoyaltyAccount` model ✅
- [x] Created `app/Models/LoyaltyAccount.php`

---

### T10 — Create `LoyaltyRule` model ✅
- [x] Created `app/Models/LoyaltyRule.php`

---

### T11 — Create `LoyaltyTransaction` model ✅
- [x] Created `app/Models/LoyaltyTransaction.php`

---

### T12 — Create `UserObserver` ✅
- [x] Created `app/Observers/UserObserver.php`

---

### T13 — Register observer in `AppServiceProvider` ✅
- [x] Added `User::observe(UserObserver::class)` in `boot()`
- [x] Imported `App\Models\User` and `App\Observers\UserObserver`

---

### T14 — Verify ✅
- [x] `php artisan migrate:fresh` — all 17 migrations passed cleanly
- [x] Tinker spot-check — patient user created → `LoyaltyAccount` auto-created (tier: standard, balance: 0)
- [x] `php artisan test` — 40/40 tests passed (136 assertions)
- [x] Enabled `RefreshDatabase` in `tests/Pest.php` (was commented out)
- [x] Added `role` field to `UserFactory::definition()`
