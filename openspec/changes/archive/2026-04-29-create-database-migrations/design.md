# Design: Create Database Migrations

## Overview

Eight Laravel migration files will be added to `database/migrations/`, ordered by their dependency chain. All tables use `timestamps()`. Foreign keys use `constrained()->cascadeOnDelete()` where record deletion should propagate.

---

## Schema

### `users`

Extends the existing users table (already migrated by the default Laravel scaffold). No new migration needed for the base columns. The role column is added here conceptually but is already part of the existing `0001_01_01_000000_create_users_table.php`. We will add a new migration if the column is absent.

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| name | string | |
| email | string | unique |
| password | string | hashed |
| phone | string, nullable | |
| role | enum: patient, doctor, admin | default: patient |
| gdpr_consent_at       | timestamp, nullable  | GDPR consent record (SR11)              |
| deletion_requested_at | timestamp, nullable  | GDPR right-to-erasure request (SR11)    |
| two_factor_secret     | text, nullable       | 2FA secret (SR3)                        |
| two_factor_recovery_codes | text, nullable   | 2FA recovery codes (SR3)                |
| two_factor_confirmed_at   | timestamp, nullable | 2FA confirmation timestamp (SR3)     |
| timestamps | | |

> **Decision**: `phone` and `role` will be added via a separate migration (`add_phone_role_to_users_table`) so the existing base migration is not modified. This keeps upgrade safety.

---

### `doctors`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| user_id | foreignId | → users, cascade delete |
| specialization | string | e.g. "Orthodontics" |
| bio | text, nullable | |
| is_active | boolean | default true |
| timestamps | | |

---

### `services`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| name | string | |
| description | text, nullable | |
| duration_minutes | unsignedSmallInteger | |
| price | decimal(10,2) | |
| complexity | enum: simple, complex | |
| timestamps | | |

---

### `doctor_schedules`

| Column       | Type                | Notes                                      |
|--------------|---------------------|--------------------------------------------|
| id           | bigIncrements       | PK                                         |
| doctor_id    | foreignId           | → doctors, cascade delete                  |
| day_of_week  | unsignedTinyInteger | 0 = Monday … 6 = Sunday                    |
| start_time   | time                |                                            |
| end_time     | time                |                                            |
| is_break     | boolean             | default false — marks break intervals      |
| timestamps   |                     |                                            |

**Index**: `(doctor_id, day_of_week)` composite index for fast lookup.
**Overlap rule**: no two rows for the same `(doctor_id, day_of_week)` may have overlapping `start_time`–`end_time` ranges. Enforced in `DoctorScheduleService`, not at DB level.

---

### `schedule_slots`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| doctor_id | foreignId | → doctors, cascade delete |
| date | date | |
| start_time | time | |
| end_time | time | |
| slot_type | enum: self, request | self = walk-in / online; request = approval needed |
| is_booked | boolean | default false |
| timestamps | | |

**Index**: `(doctor_id, date)` composite index for slot lookup queries.

---

### `appointments`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| patient_id | foreignId | → users, cascade delete |
| doctor_id | foreignId | → doctors, cascade delete |
| service_id | foreignId | → services, restrict delete |
| slot_id | foreignId | → schedule_slots, restrict delete |
| status      | enum: pending, confirmed, cancelled, completed, no_show | default: pending |
| notes       | text, nullable  | patient-provided notes at booking time   |
| doctor_notes| text, nullable  | internal notes added by the doctor       |
| timestamps | | |

> `service_id` and `slot_id` use `restrictOnDelete()` — a service or slot should not be deleted while an appointment references it.

---

### `loyalty_tiers`

| Column            | Type          | Notes                                         |
|-------------------|---------------|-----------------------------------------------|
| id                | bigIncrements | PK                                            |
| tier              | enum: standard, silver, gold | unique                         |
| points_threshold  | unsignedInteger | minimum points to reach this tier           |
| discount_bonus_pct| decimal(5,2)  | extra discount % granted to this tier         |
| timestamps        |               |                                               |

---

### `loyalty_accounts`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| patient_id | foreignId | → users, cascade delete |
| points_balance | unsignedInteger | default 0 |
| tier | enum: standard, silver, gold | default: standard |
| timestamps | | |

**Unique constraint**: `patient_id` (one account per patient).

---

### `loyalty_rules`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| service_id | foreignId | → services, cascade delete |
| points_earned | unsignedInteger | points awarded when this service is completed |
| discount_pct | decimal(5,2) | percentage discount redeemable; 0 = none |
| valid_months | unsignedTinyInteger, nullable | null = no expiry |
| timestamps | | |

---

### `loyalty_transactions`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | PK |
| loyalty_account_id | foreignId | → loyalty_accounts, cascade delete |
| appointment_id | foreignId, nullable | → appointments, set null on delete |
| points_delta | integer | positive = earn, negative = redeem |
| type | enum: earn, redeem | |
| timestamps | | |

---

## Migration Execution Order

```
1. add_phone_role_gdpr_2fa_to_users_table     (depends on: users — already exists)
2. create_doctors_table                        (depends on: users)
3. create_services_table                       (no FK dependencies)
4. create_doctor_schedules_table               (depends on: doctors)
5. create_schedule_slots_table                 (depends on: doctors)
6. create_appointments_table                   (depends on: users, doctors, services, schedule_slots)
7. create_loyalty_tiers_table                  (no FK dependencies)
8. create_loyalty_accounts_table               (depends on: users)
9. create_loyalty_rules_table                  (depends on: services)
10. create_loyalty_transactions_table          (depends on: loyalty_accounts, appointments)
```

---

## Foreign Key Cascade Strategy

| Relationship | On Delete |
|---|---|
| doctors → users | CASCADE — deleting user removes doctor profile |
| schedule_slots → doctors | CASCADE — deleting doctor removes their slots |
| appointments → patient (users) | CASCADE — deleting user removes their appointments |
| appointments → doctor (doctors) | CASCADE — deleting doctor removes their appointments |
| appointments → service | RESTRICT — protect services with active appointments |
| appointments → slot | RESTRICT — protect slots with active appointments |
| loyalty_accounts → users | CASCADE |
| loyalty_rules → services | CASCADE |
| loyalty_transactions → loyalty_accounts | CASCADE |
| loyalty_transactions → appointments | SET NULL — preserves transaction history when appointment deleted |
| doctor_schedules → doctors | CASCADE — deleting doctor removes their schedule |

---

## Conventions

- All files named with timestamp prefix: `YYYY_MM_DD_HHmmss_<name>.php`
- Use Laravel's `Schema::create()` / `Schema::table()` fluent API
- Use `$table->foreignId('x_id')->constrained()->cascadeOnDelete()` shorthand where possible
- Rollback (`down()`) must exactly reverse the `up()` method
