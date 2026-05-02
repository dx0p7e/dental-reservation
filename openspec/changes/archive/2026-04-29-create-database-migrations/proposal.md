# Proposal: Create Database Migrations

## What

Create all core database migrations for the dental clinic reservation system. This includes:

- `users` — patients, doctors and admins share a single users table with a role discriminator
- `doctors` — doctor profile linked to a user (specialization, bio, active status)
- `services` — dental services offered (name, duration, price, complexity)
- `doctor_schedules` — weekly recurring work-time and break intervals per doctor
- `schedule_slots` — time slots per doctor per day, bookable directly (self) or by request
- `appointments` — links a patient, doctor, service and slot; tracks full status lifecycle
- `loyalty_tiers` — point thresholds and bonus discounts that define each tier level
- `loyalty_accounts` — per-patient loyalty wallet (points balance, tier)
- `loyalty_rules` — per-service rules that define how many points a service earns and what discount it grants
- `loyalty_transactions` — audit log of every point earn or redemption tied to an appointment

## Why

The dental reservation system requires a well-structured relational schema before any feature work can begin. All application models, controllers, factories, seeders, and tests depend on these migrations being in place. Defining them up front ensures:

- **Consistency** — all developers and CI environments share the same schema baseline
- **Referential integrity** — foreign-key constraints prevent orphaned records at the database level
- **Feature readiness** — scheduling, booking, loyalty, and user-management features can be built on a stable foundation

## Non-goals

- Seeding production data — that belongs in `DatabaseSeeder` or separate seeders
- Eloquent model definitions or relationships — out of scope for this change
- API routes or controllers — out of scope for this change
- UI / front-end work — out of scope for this change
