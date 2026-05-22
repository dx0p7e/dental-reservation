# Database Schema Summary

> Generated from actual `Schema::create` calls in `database/migrations/` and relationship methods in `app/Models/`.

---

## Tables

---

### users

| Column                    | Type                       | Modifiers         |
| ------------------------- | -------------------------- | ----------------- |
| id                        | bigIncrements              | primary key       |
| name                      | string                     | —                 |
| email                     | string                     | unique            |
| email_verified_at         | timestamp                  | nullable          |
| password                  | string                     | —                 |
| two_factor_secret         | text                       | nullable          |
| two_factor_recovery_codes | text                       | nullable          |
| two_factor_confirmed_at   | timestamp                  | nullable          |
| phone                     | string                     | nullable          |
| notification_channel      | enum(email,sms,both)       | default 'email'   |
| phone_verified_at         | timestamp                  | nullable          |
| smart_id_verified_at      | timestamp                  | nullable          |
| role                      | enum(patient,doctor,admin) | default 'patient' |
| remember_token            | string                     | nullable          |
| gdpr_consent_at           | timestamp                  | nullable          |
| deletion_requested_at     | timestamp                  | nullable          |
| created_at / updated_at   | timestamps                 | —                 |

**Foreign keys:** none

**Unique constraints:** email

---

### password_reset_tokens

| Column     | Type      | Modifiers   |
| ---------- | --------- | ----------- |
| email      | string    | primary key |
| token      | string    | —           |
| created_at | timestamp | nullable    |

**Foreign keys:** none

**Unique constraints:** none (email is primary)

---

### sessions

| Column        | Type                        | Modifiers       |
| ------------- | --------------------------- | --------------- |
| id            | string                      | primary key     |
| user_id       | foreignId (bigint unsigned) | nullable, index |
| ip_address    | string(45)                  | nullable        |
| user_agent    | text                        | nullable        |
| payload       | longText                    | —               |
| last_activity | integer                     | index           |

**Foreign keys:** none — user_id has no `->constrained()` or `->foreign()` call; it is just an indexed column

**Unique constraints:** none

---

### cache

| Column     | Type       | Modifiers   |
| ---------- | ---------- | ----------- |
| key        | string     | primary key |
| value      | mediumText | —           |
| expiration | integer    | index       |

**Foreign keys:** none

**Unique constraints:** none

---

### cache_locks

| Column     | Type    | Modifiers   |
| ---------- | ------- | ----------- |
| key        | string  | primary key |
| owner      | string  | —           |
| expiration | integer | index       |

**Foreign keys:** none

**Unique constraints:** none

---

### jobs

| Column       | Type                | Modifiers   |
| ------------ | ------------------- | ----------- |
| id           | bigIncrements       | primary key |
| queue        | string              | index       |
| payload      | longText            | —           |
| attempts     | unsignedTinyInteger | —           |
| reserved_at  | unsignedInteger     | nullable    |
| available_at | unsignedInteger     | —           |
| created_at   | unsignedInteger     | —           |

**Foreign keys:** none

**Unique constraints:** none

---

### job_batches

| Column         | Type       | Modifiers   |
| -------------- | ---------- | ----------- |
| id             | string     | primary key |
| name           | string     | —           |
| total_jobs     | integer    | —           |
| pending_jobs   | integer    | —           |
| failed_jobs    | integer    | —           |
| failed_job_ids | longText   | —           |
| options        | mediumText | nullable    |
| cancelled_at   | integer    | nullable    |
| created_at     | integer    | —           |
| finished_at    | integer    | nullable    |

**Foreign keys:** none

**Unique constraints:** none

---

### failed_jobs

| Column     | Type          | Modifiers   |
| ---------- | ------------- | ----------- |
| id         | bigIncrements | primary key |
| uuid       | string        | unique      |
| connection | text          | —           |
| queue      | text          | —           |
| payload    | longText      | —           |
| exception  | longText      | —           |
| failed_at  | timestamp     | useCurrent  |

**Foreign keys:** none

**Unique constraints:** uuid

---

### doctors

| Column                  | Type          | Modifiers                               |
| ----------------------- | ------------- | --------------------------------------- |
| id                      | bigIncrements | primary key                             |
| user_id                 | foreignId     | constrained → users.id, cascadeOnDelete |
| specialization          | string        | —                                       |
| bio                     | text          | nullable                                |
| photo_path              | string        | nullable                                |
| is_active               | boolean       | default true                            |
| created_at / updated_at | timestamps    | —                                       |

**Foreign keys:** user_id → users.id (cascade on delete)

**Unique constraints:** none

---

### services

| Column                  | Type                 | Modifiers   |
| ----------------------- | -------------------- | ----------- |
| id                      | bigIncrements        | primary key |
| name                    | string               | —           |
| description             | text                 | nullable    |
| duration_minutes        | unsignedSmallInteger | —           |
| price                   | decimal(10,2)        | —           |
| complexity              | enum(simple,complex) | —           |
| created_at / updated_at | timestamps           | —           |

**Foreign keys:** none

**Unique constraints:** none

---

### doctor_schedules

| Column                  | Type                 | Modifiers                                 |
| ----------------------- | -------------------- | ----------------------------------------- |
| id                      | bigIncrements        | primary key                               |
| doctor_id               | foreignId            | constrained → doctors.id, cascadeOnDelete |
| day_of_week             | unsignedTinyInteger  | —                                         |
| start_time              | time                 | —                                         |
| end_time                | time                 | —                                         |
| slot_duration_minutes   | unsignedSmallInteger | —                                         |
| is_active               | boolean              | default true                              |
| created_at / updated_at | timestamps           | —                                         |

**Foreign keys:** doctor_id → doctors.id (cascade on delete)

**Indexes:** composite [doctor_id, day_of_week]

**Unique constraints:** none

---

### schedule_slots

| Column                  | Type               | Modifiers                                 |
| ----------------------- | ------------------ | ----------------------------------------- |
| id                      | bigIncrements      | primary key                               |
| doctor_id               | foreignId          | constrained → doctors.id, cascadeOnDelete |
| date                    | date               | —                                         |
| start_time              | time               | —                                         |
| end_time                | time               | —                                         |
| slot_type               | enum(self,request) | —                                         |
| is_booked               | boolean            | default false                             |
| created_at / updated_at | timestamps         | —                                         |

**Foreign keys:** doctor_id → doctors.id (cascade on delete)

**Indexes:** composite [doctor_id, date]

**Unique constraints:** none

---

### appointments

| Column                  | Type                                                | Modifiers                                                   |
| ----------------------- | --------------------------------------------------- | ----------------------------------------------------------- |
| id                      | bigIncrements                                       | primary key                                                 |
| patient_id              | foreignId                                           | constrained → users.id, cascadeOnDelete, nullable           |
| doctor_id               | foreignId                                           | constrained → doctors.id, cascadeOnDelete, nullable         |
| service_id              | foreignId                                           | constrained → services.id, restrictOnDelete                 |
| slot_id                 | foreignId                                           | constrained → schedule_slots.id, restrictOnDelete, nullable |
| discount_pct            | decimal(5,2)                                        | default 0.00                                                |
| final_price             | decimal(8,2)                                        | nullable                                                    |
| preferred_date          | date                                                | nullable                                                    |
| rescheduled_at          | timestamp                                           | nullable                                                    |
| status                  | enum(pending,confirmed,cancelled,completed,no_show) | default 'pending'                                           |
| notes                   | text                                                | nullable                                                    |
| doctor_notes            | text                                                | nullable                                                    |
| reminder_sent_at        | timestamp                                           | nullable                                                    |
| created_at / updated_at | timestamps                                          | —                                                           |

**Foreign keys:**

- patient_id → users.id (cascade on delete)
- doctor_id → doctors.id (cascade on delete)
- service_id → services.id (restrict on delete)
- slot_id → schedule_slots.id (restrict on delete)

**Unique constraints:** none

---

### loyalty_tiers

| Column                  | Type                       | Modifiers         |
| ----------------------- | -------------------------- | ----------------- |
| id                      | bigIncrements              | primary key       |
| tier                    | enum(standard,silver,gold) | unique            |
| points_threshold        | unsignedInteger            | —                 |
| discount_bonus_pct      | decimal(5,2)               | —                 |
| color                   | string(30)                 | default '#6b7280' |
| created_at / updated_at | timestamps                 | —                 |

**Foreign keys:** none

**Unique constraints:** tier

---

### loyalty_accounts

| Column                  | Type                       | Modifiers                               |
| ----------------------- | -------------------------- | --------------------------------------- |
| id                      | bigIncrements              | primary key                             |
| patient_id              | foreignId                  | constrained → users.id, cascadeOnDelete |
| points_balance          | unsignedInteger            | default 0                               |
| tier                    | enum(standard,silver,gold) | default 'standard'                      |
| created_at / updated_at | timestamps                 | —                                       |

**Foreign keys:** patient_id → users.id (cascade on delete)

**Unique constraints:** patient_id

---

### loyalty_rules

| Column                  | Type                | Modifiers                                  |
| ----------------------- | ------------------- | ------------------------------------------ |
| id                      | bigIncrements       | primary key                                |
| service_id              | foreignId           | constrained → services.id, cascadeOnDelete |
| points_earned           | unsignedInteger     | —                                          |
| discount_pct            | decimal(5,2)        | —                                          |
| valid_months            | unsignedTinyInteger | nullable                                   |
| is_active               | boolean             | default true                               |
| created_at / updated_at | timestamps          | —                                          |

**Foreign keys:** service_id → services.id (cascade on delete)

**Unique constraints:** service_id

---

### loyalty_transactions

| Column                  | Type              | Modifiers                                             |
| ----------------------- | ----------------- | ----------------------------------------------------- |
| id                      | bigIncrements     | primary key                                           |
| loyalty_account_id      | foreignId         | constrained → loyalty_accounts.id, cascadeOnDelete    |
| appointment_id          | foreignId         | nullable, constrained → appointments.id, nullOnDelete |
| points_delta            | integer           | —                                                     |
| type                    | enum(earn,redeem) | —                                                     |
| created_at / updated_at | timestamps        | —                                                     |

**Foreign keys:**

- loyalty_account_id → loyalty_accounts.id (cascade on delete)
- appointment_id → appointments.id (null on delete)

**Unique constraints:** none

---

### activity_log

| Column                  | Type               | Modifiers                      |
| ----------------------- | ------------------ | ------------------------------ |
| id                      | bigIncrements      | primary key                    |
| log_name                | string             | nullable, index                |
| description             | text               | —                              |
| subject_type            | string             | nullable (from nullableMorphs) |
| subject_id              | unsignedBigInteger | nullable (from nullableMorphs) |
| event                   | string             | nullable                       |
| causer_type             | string             | nullable (from nullableMorphs) |
| causer_id               | unsignedBigInteger | nullable (from nullableMorphs) |
| attribute_changes       | json               | nullable                       |
| properties              | json               | nullable                       |
| created_at / updated_at | timestamps         | —                              |

**Foreign keys:** none (morphs have no FK constraints)

**Indexes:** log_name; composite [subject_type, subject_id] named 'subject'; composite [causer_type, causer_id] named 'causer'

**Unique constraints:** none

---

### permissions _(Spatie Permission)_

| Column                  | Type          | Modifiers   |
| ----------------------- | ------------- | ----------- |
| id                      | bigIncrements | primary key |
| name                    | string        | —           |
| guard_name              | string        | —           |
| created_at / updated_at | timestamps    | —           |

**Foreign keys:** none

**Unique constraints:** [name, guard_name]

---

### roles _(Spatie Permission)_

| Column                  | Type          | Modifiers   |
| ----------------------- | ------------- | ----------- |
| id                      | bigIncrements | primary key |
| name                    | string        | —           |
| guard_name              | string        | —           |
| created_at / updated_at | timestamps    | —           |

**Foreign keys:** none

**Unique constraints:** [name, guard_name]

---

### model_has_permissions _(Spatie Permission)_

| Column        | Type               | Modifiers |
| ------------- | ------------------ | --------- |
| permission_id | unsignedBigInteger | —         |
| model_type    | string             | —         |
| model_id      | unsignedBigInteger | —         |

**Foreign keys:** permission_id → permissions.id (cascade on delete)

**Primary key:** [permission_id, model_id, model_type]

**Indexes:** composite [model_id, model_type]

---

### model_has_roles _(Spatie Permission)_

| Column     | Type               | Modifiers |
| ---------- | ------------------ | --------- |
| role_id    | unsignedBigInteger | —         |
| model_type | string             | —         |
| model_id   | unsignedBigInteger | —         |

**Foreign keys:** role_id → roles.id (cascade on delete)

**Primary key:** [role_id, model_id, model_type]

**Indexes:** composite [model_id, model_type]

---

### role_has_permissions _(Spatie Permission)_

| Column        | Type               | Modifiers |
| ------------- | ------------------ | --------- |
| permission_id | unsignedBigInteger | —         |
| role_id       | unsignedBigInteger | —         |

**Foreign keys:**

- permission_id → permissions.id (cascade on delete)
- role_id → roles.id (cascade on delete)

**Primary key:** [permission_id, role_id]

---

### passkeys

| Column                  | Type          | Modifiers                               |
| ----------------------- | ------------- | --------------------------------------- |
| id                      | bigIncrements | primary key                             |
| user_id                 | foreignId     | constrained → users.id, cascadeOnDelete |
| name                    | string        | —                                       |
| credential_id           | string        | unique                                  |
| credential              | json          | —                                       |
| last_used_at            | timestamp     | nullable                                |
| created_at / updated_at | timestamps    | —                                       |

**Foreign keys:** user_id → users.id (cascade on delete)

**Indexes:** user_id

**Unique constraints:** credential_id

---

### personal_access_tokens _(Sanctum)_

| Column                  | Type               | Modifiers       |
| ----------------------- | ------------------ | --------------- |
| id                      | bigIncrements      | primary key     |
| tokenable_type          | string             | (from morphs)   |
| tokenable_id            | unsignedBigInteger | (from morphs)   |
| name                    | text               | —               |
| token                   | string(64)         | unique          |
| abilities               | text               | nullable        |
| last_used_at            | timestamp          | nullable        |
| expires_at              | timestamp          | nullable, index |
| created_at / updated_at | timestamps         | —               |

**Foreign keys:** none (morphs)

**Unique constraints:** token

---

### phone_verifications

| Column     | Type          | Modifiers                               |
| ---------- | ------------- | --------------------------------------- |
| id         | bigIncrements | primary key                             |
| user_id    | foreignId     | constrained → users.id, cascadeOnDelete |
| code       | string(6)     | —                                       |
| expires_at | timestamp     | —                                       |
| created_at | timestamp     | —                                       |

Note: no `updated_at` column; `->timestamps()` is not used — `created_at` is defined manually only.

**Foreign keys:** user_id → users.id (cascade on delete)

**Unique constraints:** none

---

### doctor_service _(pivot)_

| Column     | Type               | Modifiers |
| ---------- | ------------------ | --------- |
| doctor_id  | unsignedBigInteger | —         |
| service_id | unsignedBigInteger | —         |

**Foreign keys:**

- doctor_id → doctors.id (cascade on delete)
- service_id → services.id (cascade on delete)

**Primary key:** [doctor_id, service_id]

---

### patient_reviews

| Column                  | Type                 | Modifiers                                      |
| ----------------------- | -------------------- | ---------------------------------------------- |
| id                      | bigIncrements        | primary key                                    |
| patient_id              | foreignId            | nullable, constrained → users.id, nullOnDelete |
| rating                  | tinyInteger unsigned | —                                              |
| title                   | string(150)          | nullable                                       |
| body                    | text                 | —                                              |
| is_published            | boolean              | default true                                   |
| created_at / updated_at | timestamps           | —                                              |

**Foreign keys:** patient_id → users.id (null on delete)

**Unique constraints:** patient_id

---

## Model Relationships

### User

- `hasOne(Doctor::class)` — FK: doctors.user_id
- `hasMany(Appointment::class, 'patient_id')` — FK: appointments.patient_id
- `hasOne(LoyaltyAccount::class, 'patient_id')` — FK: loyalty_accounts.patient_id
- `hasOne(PatientReview::class, 'patient_id')` — FK: patient_reviews.patient_id
- `hasManyThrough(LoyaltyTransaction::class, LoyaltyAccount::class, 'patient_id', 'loyalty_account_id')` — through loyalty_accounts

### Doctor

- `belongsTo(User::class)` — FK: doctors.user_id
- `hasMany(DoctorSchedule::class)` — FK: doctor_schedules.doctor_id
- `hasMany(ScheduleSlot::class)` — FK: schedule_slots.doctor_id
- `hasMany(Appointment::class)` — FK: appointments.doctor_id
- `belongsToMany(Service::class, 'doctor_service')` — pivot table: doctor_service

### Appointment

- `belongsTo(User::class, 'patient_id')` — FK: appointments.patient_id
- `belongsTo(Doctor::class)` — FK: appointments.doctor_id
- `belongsTo(Service::class)` — FK: appointments.service_id
- `belongsTo(ScheduleSlot::class, 'slot_id')` — FK: appointments.slot_id
- `hasMany(LoyaltyTransaction::class)` — FK: loyalty_transactions.appointment_id

### Service

- `hasOne(LoyaltyRule::class)` — FK: loyalty_rules.service_id
- `hasMany(Appointment::class)` — FK: appointments.service_id
- `belongsToMany(Doctor::class, 'doctor_service')` — pivot table: doctor_service

### DoctorSchedule

- `belongsTo(Doctor::class)` — FK: doctor_schedules.doctor_id

### ScheduleSlot

- `belongsTo(Doctor::class)` — FK: schedule_slots.doctor_id
- `hasOne(Appointment::class, 'slot_id')` — FK: appointments.slot_id

### LoyaltyAccount

- `belongsTo(User::class, 'patient_id')` — FK: loyalty_accounts.patient_id
- `hasMany(LoyaltyTransaction::class)` — FK: loyalty_transactions.loyalty_account_id

### LoyaltyRule

- `belongsTo(Service::class)` — FK: loyalty_rules.service_id

### LoyaltyTier

- No relationships defined.

### LoyaltyTransaction

- `belongsTo(LoyaltyAccount::class)` — FK: loyalty_transactions.loyalty_account_id
- `belongsTo(Appointment::class)` — FK: loyalty_transactions.appointment_id

### PatientReview

- `belongsTo(User::class, 'patient_id')` — FK: patient_reviews.patient_id

### PhoneVerification

- `belongsTo(User::class)` — FK: phone_verifications.user_id

---

## Logical-Only Relationships

None. Every model relationship has a corresponding FK constraint in the migrations.

**Design note:** `loyalty_accounts.tier` is an enum with the same values as `loyalty_tiers.tier`, and `LoyaltyTier` has no model relationships. The connection between the tier stored on an account and the `loyalty_tiers` configuration table is purely by matching enum string values — there is no FK and no model relationship wiring them together.
