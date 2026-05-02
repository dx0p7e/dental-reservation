# Design: Create Eloquent Models

## Overview

Ten Eloquent model files plus one observer. Two Composer packages must be installed first. The existing `User` model is updated in-place; all other models are new files.

---

## Package Requirements

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | `HasRoles` trait + role/permission tables |
| `spatie/laravel-activitylog` | `LogsActivity` trait + activity_log table |
| `laravel/fortify` | `TwoFactorAuthenticatable` trait + 2FA endpoints |

The spatie packages are installed via Composer and their migrations published before any model work. `laravel/fortify` is already declared in `composer.json`; its service provider must be published if not already done.

---

## Models

### `User` (`app/Models/User.php`) — **update existing**

**Traits:** `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`, `HasRoles`  
**Implements:** `MustVerifyEmail`

| Concern | Detail |
|---|---|
| `$fillable` | `name`, `email`, `password`, `phone`, `role` |
| `$hidden` | `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token` |
| `$casts` | `email_verified_at: datetime`, `password: hashed`, `two_factor_confirmed_at: datetime`, `gdpr_consent_at: datetime`, `deletion_requested_at: datetime` |
| `isPatient()` | helper: `$this->role === 'patient'` |
| `isDoctor()` | helper: `$this->role === 'doctor'` |
| `isAdmin()` | helper: `$this->role === 'admin'` |
| **Relationships** | |
| `doctor()` | `hasOne(Doctor::class)` |
| `appointments()` | `hasMany(Appointment::class, 'patient_id')` |
| `loyaltyAccount()` | `hasOne(LoyaltyAccount::class, 'patient_id')` |

> The `#[Fillable]` / `#[Hidden]` PHP 8 attributes used in the scaffold are replaced by standard `$fillable` / `$hidden` array properties to ensure compatibility with `HasRoles` and the additional columns.

---

### `Doctor` (`app/Models/Doctor.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `user_id`, `specialization`, `bio`, `is_active` |
| `$casts` | `is_active: boolean` |
| **Relationships** | |
| `user()` | `belongsTo(User::class)` |
| `schedules()` | `hasMany(DoctorSchedule::class)` |
| `slots()` | `hasMany(ScheduleSlot::class)` |
| `appointments()` | `hasMany(Appointment::class)` |

---

### `Service` (`app/Models/Service.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `name`, `description`, `duration_minutes`, `price`, `complexity` |
| `$casts` | `price: decimal:2`, `duration_minutes: integer` |
| **Relationships** | |
| `loyaltyRule()` | `hasOne(LoyaltyRule::class)` |
| `appointments()` | `hasMany(Appointment::class)` |

---

### `DoctorSchedule` (`app/Models/DoctorSchedule.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `doctor_id`, `day_of_week`, `start_time`, `end_time`, `is_break` |
| `$casts` | `is_break: boolean`, `day_of_week: integer` |
| **Relationships** | |
| `doctor()` | `belongsTo(Doctor::class)` |

---

### `ScheduleSlot` (`app/Models/ScheduleSlot.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `doctor_id`, `date`, `start_time`, `end_time`, `slot_type`, `is_booked` |
| `$casts` | `date: date`, `is_booked: boolean` |
| **Relationships** | |
| `doctor()` | `belongsTo(Doctor::class)` |
| `appointment()` | `hasOne(Appointment::class, 'slot_id')` |

---

### `Appointment` (`app/Models/Appointment.php`) — **new**

**Traits:** `HasFactory`, `LogsActivity` (from `Spatie\Activitylog\Traits\LogsActivity`)

Activity log configuration:
- `getActivitylogOptions()` returns `LogOptions::defaults()->logOnly(['status', 'notes', 'doctor_notes'])->logOnlyDirty()`

| Concern | Detail |
|---|---|
| `$fillable` | `patient_id`, `doctor_id`, `service_id`, `slot_id`, `status`, `notes`, `doctor_notes` |
| `$casts` | `status: App\Enums\AppointmentStatus` |
| **Relationships** | |
| `patient()` | `belongsTo(User::class, 'patient_id')` |
| `doctor()` | `belongsTo(Doctor::class)` |
| `service()` | `belongsTo(Service::class)` |
| `slot()` | `belongsTo(ScheduleSlot::class, 'slot_id')` |
| `loyaltyTransactions()` | `hasMany(LoyaltyTransaction::class)` |

> Create `app/Enums/AppointmentStatus.php` as a string-backed enum with cases: `Pending`, `Confirmed`, `Cancelled`, `Completed`, `NoShow`.

---

### `LoyaltyTier` (`app/Models/LoyaltyTier.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `tier`, `points_threshold`, `discount_bonus_pct` |
| `$casts` | `points_threshold: integer`, `discount_bonus_pct: decimal:2` |
| **Relationships** | (none — referenced by `LoyaltyAccount` via enum value, not FK) |

---

### `LoyaltyAccount` (`app/Models/LoyaltyAccount.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `patient_id`, `points_balance`, `tier` |
| `$casts` | `points_balance: integer` |
| **Relationships** | |
| `patient()` | `belongsTo(User::class, 'patient_id')` |
| `transactions()` | `hasMany(LoyaltyTransaction::class)` |

---

### `LoyaltyRule` (`app/Models/LoyaltyRule.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `service_id`, `points_earned`, `discount_pct`, `valid_months` |
| `$casts` | `points_earned: integer`, `discount_pct: decimal:2`, `valid_months: integer` |
| **Relationships** | |
| `service()` | `belongsTo(Service::class)` |

---

### `LoyaltyTransaction` (`app/Models/LoyaltyTransaction.php`) — **new**

**Traits:** `HasFactory`

| Concern | Detail |
|---|---|
| `$fillable` | `loyalty_account_id`, `appointment_id`, `points_delta`, `type` |
| `$casts` | `points_delta: integer` |
| **Relationships** | |
| `loyaltyAccount()` | `belongsTo(LoyaltyAccount::class)` |
| `appointment()` | `belongsTo(Appointment::class)` |

---

## Observer: `UserObserver`

**File:** `app/Observers/UserObserver.php`

Hooks into the `created` event. When a new `User` has `role === 'patient'`, it immediately creates a `LoyaltyAccount` with `points_balance = 0` and `tier = 'standard'`.

```
created(User $user):
  if $user->role === 'patient':
    LoyaltyAccount::create([
      'patient_id'     => $user->id,
      'points_balance' => 0,
      'tier'           => 'standard',
    ])
```

**Registration:** The observer is registered in `AppServiceProvider::boot()` via `User::observe(UserObserver::class)`.

---

## File Map

| File | Action |
|---|---|
| `app/Models/User.php` | Update |
| `app/Models/Doctor.php` | Create |
| `app/Models/Service.php` | Create |
| `app/Models/DoctorSchedule.php` | Create |
| `app/Models/ScheduleSlot.php` | Create |
| `app/Models/Appointment.php` | Create |
| `app/Models/LoyaltyTier.php` | Create |
| `app/Models/LoyaltyAccount.php` | Create |
| `app/Models/LoyaltyRule.php` | Create |
| `app/Models/LoyaltyTransaction.php` | Create |
| `app/Enums/AppointmentStatus.php` | Create |
| `app/Observers/UserObserver.php` | Create |
| `app/Providers/AppServiceProvider.php` | Update (register observer) |
