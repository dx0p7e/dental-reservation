# Tasks: Create Database Migrations

## Implementation Tasks

### T1 — Add `phone`, `role`, GDPR and 2FA columns to `users` table
- [x] Create migration `...2025_08_14_180000_add_phone_role_gdpr_2fa_to_users_table.php`
- [x] In `up()`: add `phone` (string, nullable), `role` (enum: patient/doctor/admin, default patient), `gdpr_consent_at` (timestamp, nullable), `deletion_requested_at` (timestamp, nullable), `two_factor_secret` (text, nullable), `two_factor_recovery_codes` (text, nullable), `two_factor_confirmed_at` (timestamp, nullable)
- [x] In `down()`: drop all seven columns

---

### T2 — Create `doctors` table
- [x] Create migration `database/migrations/2025_08_14_180001_create_doctors_table.php`
- [x] Columns: `id`, `user_id` (FK → users, cascadeOnDelete), `specialization` (string), `bio` (text, nullable), `is_active` (boolean, default true), `timestamps`
- [x] In `down()`: `Schema::dropIfExists('doctors')`

---

### T3 — Create `services` table
- [x] Create migration `database/migrations/2025_08_14_180002_create_services_table.php`
- [x] Columns: `id`, `name` (string), `description` (text, nullable), `duration_minutes` (unsignedSmallInteger), `price` (decimal 10,2), `complexity` (enum: simple/complex), `timestamps`
- [x] In `down()`: `Schema::dropIfExists('services')`

---

### T3b — Create `doctor_schedules` table
- [x] Create migration `...2025_08_14_180003_create_doctor_schedules_table.php`
- [x] Columns: `id`, `doctor_id` (FK → doctors, cascadeOnDelete), `day_of_week` (unsignedTinyInteger), `start_time` (time), `end_time` (time), `is_break` (boolean, default false), `timestamps`
- [x] Add composite index on `(doctor_id, day_of_week)`
- [x] In `down()`: `Schema::dropIfExists('doctor_schedules')`

---

### T4 — Create `schedule_slots` table
- [x] Create migration `database/migrations/2025_08_14_180003_create_schedule_slots_table.php`
- [x] Columns: `id`, `doctor_id` (FK → doctors, cascadeOnDelete), `date` (date), `start_time` (time), `end_time` (time), `slot_type` (enum: self/request), `is_booked` (boolean, default false), `timestamps`
- [x] Add composite index on `(doctor_id, date)` for fast slot lookup
- [x] In `down()`: `Schema::dropIfExists('schedule_slots')`

---

### T5 — Create `appointments` table
- [x] Create migration `database/migrations/2025_08_14_180004_create_appointments_table.php`
- [x] Columns: `id`, `patient_id` (FK → users, cascadeOnDelete), `doctor_id` (FK → doctors, cascadeOnDelete), `service_id` (FK → services, restrictOnDelete), `slot_id` (FK → schedule_slots, restrictOnDelete), `status` (enum: pending/confirmed/cancelled/complete/no_show, default pending), `notes` (text, nullable), `timestamps`, `doctor_notes` (text, nullable)
- [x] In `down()`: `Schema::dropIfExists('appointments')`

---

### T6 — Create `loyalty_accounts` table
- [x] Create migration `database/migrations/2025_08_14_180005_create_loyalty_accounts_table.php`
- [x] Columns: `id`, `patient_id` (FK → users, cascadeOnDelete), `points_balance` (unsignedInteger, default 0), `tier` (enum: standard/silver/gold, default standard), `timestamps`
- [x] Add unique constraint on `patient_id`
- [x] In `down()`: `Schema::dropIfExists('loyalty_accounts')`

---

### T6b — Create `loyalty_tiers` table
- [x] Create migration `...2025_08_14_180005b_create_loyalty_tiers_table.php`
- [x] Columns: `id`, `tier` (enum: standard/silver/gold, unique), `points_threshold` (unsignedInteger), `discount_bonus_pct` (decimal 5,2), `timestamps`
- [x] In `down()`: `Schema::dropIfExists('loyalty_tiers')`

---

### T7 — Create `loyalty_rules` table
- [x] Create migration `database/migrations/2025_08_14_180006_create_loyalty_rules_table.php`
- [x] Columns: `id`, `service_id` (FK → services, cascadeOnDelete), `points_earned` (unsignedInteger), `discount_pct` (decimal 5,2), `valid_months` (unsignedTinyInteger, nullable), `timestamps`
- [x] In `down()`: `Schema::dropIfExists('loyalty_rules')`

---

### T8 — Create `loyalty_transactions` table
- [x] Create migration `database/migrations/2025_08_14_180007_create_loyalty_transactions_table.php`
- [x] Columns: `id`, `loyalty_account_id` (FK → loyalty_accounts, cascadeOnDelete), `appointment_id` (FK → appointments, nullable, nullOnDelete), `points_delta` (integer), `type` (enum: earn/redeem), `timestamps`
- [x] In `down()`: `Schema::dropIfExists('loyalty_transactions')`

---

### T9 — Verify migrations run cleanly
- [x] Run `php artisan migrate:fresh` locally
- [x] Confirm all 10 new migrations execute without error
- [x] Run `php artisan migrate:rollback --step=10` to verify `down()` methods are correct
