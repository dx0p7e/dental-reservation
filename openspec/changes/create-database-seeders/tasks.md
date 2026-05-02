# Tasks: Create Database Seeders

## Implementation Tasks

### T1 — Create `RoleSeeder` ✅
- [x] Create `database/seeders/RoleSeeder.php`
- [x] Import `Spatie\Permission\Models\Role` and `Illuminate\Database\Seeder`
- [x] In `run()`: call `Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web'])`, same for `doctor` and `admin`

---

### T2 — Create `LoyaltyTierSeeder` ✅
- [x] Create `database/seeders/LoyaltyTierSeeder.php`
- [x] Import `App\Models\LoyaltyTier` and `Illuminate\Database\Seeder`
- [x] In `run()`: define the three tiers array (`standard`/0/0.00, `silver`/500/5.00, `gold`/1500/10.00)
- [x] Loop and call `LoyaltyTier::updateOrCreate(['tier' => $data['tier']], $data)` for each

---

### T3 — Create `AdminUserSeeder` ✅
- [x] Create `database/seeders/AdminUserSeeder.php`
- [x] Import `App\Models\User`, `Illuminate\Support\Facades\Hash`, and `Illuminate\Database\Seeder`
- [x] In `run()`: call `User::firstOrCreate(['email' => env('ADMIN_EMAIL', 'admin@example.com')], ['name' => env('ADMIN_NAME', 'Admin'), 'password' => Hash::make(env('ADMIN_PASSWORD', 'changeme')), 'role' => 'admin', 'email_verified_at' => now()])`
- [x] Call `$admin->assignRole('admin')` on the returned user

---

### T4 — Update `DatabaseSeeder` ✅
- [x] Open `database/seeders/DatabaseSeeder.php`
- [x] Remove the scaffold `User::factory()->create(...)` call
- [x] Replace `run()` body with `$this->call([RoleSeeder::class, LoyaltyTierSeeder::class, AdminUserSeeder::class])`

---

### T5 — Update `.env.example` ✅
- [x] Added `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` block to `.env.example`
- [x] Added same variables to `.env`

---

### T6 — Verify ✅
- [x] `php artisan migrate:fresh --seed` — all 17 migrations + 3 seeders passed
- [x] Three roles confirmed: `admin`, `doctor`, `patient`
- [x] Three loyalty tiers confirmed: `standard`/0pts/0%, `silver`/500pts/5%, `gold`/1500pts/10%
- [x] Admin user confirmed: `admin@example.com`, spatie role `admin`, `email_verified_at` set
- [x] `php artisan test` — 40/40 tests passed (136 assertions)
