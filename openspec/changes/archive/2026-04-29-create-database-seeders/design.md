# Design: Create Database Seeders

## Overview

Four seeder files. Three new seeders plus an update to the existing `DatabaseSeeder`. All seeders use `firstOrCreate` / `updateOrCreate` so they are safe to run multiple times.

---

## Execution Order

```
DatabaseSeeder
├── RoleSeeder          (no dependencies)
├── LoyaltyTierSeeder   (no dependencies)
└── AdminUserSeeder     (depends on RoleSeeder — 'admin' role must exist)
```

---

## Seeders

### `RoleSeeder` (`database/seeders/RoleSeeder.php`) — **new**

Creates the three application roles using `spatie/laravel-permission`.

```php
use Spatie\Permission\Models\Role;

Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
Role::firstOrCreate(['name' => 'doctor',  'guard_name' => 'web']);
Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
```

- Uses `firstOrCreate` keyed on `['name', 'guard_name']` so re-runs are safe
- Guard name is `web` (the application's default auth guard)

---

### `LoyaltyTierSeeder` (`database/seeders/LoyaltyTierSeeder.php`) — **new**

Seeds the `loyalty_tiers` table with the three baseline tiers. Uses `updateOrCreate` keyed on `tier` so values can be corrected by re-running.

| `tier`     | `points_threshold` | `discount_bonus_pct` |
|------------|--------------------|----------------------|
| `standard` | `0`                | `0.00`               |
| `silver`   | `500`              | `5.00`               |
| `gold`     | `1500`             | `10.00`              |

```php
use App\Models\LoyaltyTier;

$tiers = [
    ['tier' => 'standard', 'points_threshold' => 0,    'discount_bonus_pct' => 0.00],
    ['tier' => 'silver',   'points_threshold' => 500,  'discount_bonus_pct' => 5.00],
    ['tier' => 'gold',     'points_threshold' => 1500, 'discount_bonus_pct' => 10.00],
];

foreach ($tiers as $data) {
    LoyaltyTier::updateOrCreate(['tier' => $data['tier']], $data);
}
```

---

### `AdminUserSeeder` (`database/seeders/AdminUserSeeder.php`) — **new**

Creates one admin user from environment variables. Assigns the `admin` role via spatie permission.

**Required `.env` variables** (add to `.env.example`):

```
ADMIN_NAME="Admin"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=changeme
```

**Logic:**

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::firstOrCreate(
    ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
    [
        'name'               => env('ADMIN_NAME', 'Admin'),
        'password'           => Hash::make(env('ADMIN_PASSWORD', 'changeme')),
        'role'               => 'admin',
        'email_verified_at'  => now(),
    ]
);

$admin->assignRole('admin');
```

- Keyed on `email` so re-runs don't create duplicates
- Uses `env()` with safe fallbacks (local dev only — production must override in `.env`)
- Calls `$admin->assignRole('admin')` (spatie method — idempotent if role already assigned)
- `email_verified_at` is set to `now()` so the admin can log in immediately without going through email verification.

---

### `DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`) — **update existing**

Replace the scaffold content with:

```php
public function run(): void
{
    $this->call([
        RoleSeeder::class,
        LoyaltyTierSeeder::class,
        AdminUserSeeder::class,
    ]);
}
```

Remove the existing `User::factory()->create(...)` scaffold call. Do not add any factory calls — those belong in a separate change.

---

## `.env.example` Addition

Add the following block after the `APP_*` section:

```
# Seeder: admin bootstrap account
ADMIN_NAME="Admin"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=changeme
```

---

## Idempotency Summary

| Seeder             | Strategy       | Key                          |
|--------------------|----------------|------------------------------|
| `RoleSeeder`       | `firstOrCreate` | `name` + `guard_name`        |
| `LoyaltyTierSeeder`| `updateOrCreate`| `tier`                       |
| `AdminUserSeeder`  | `firstOrCreate` | `email`                      |
