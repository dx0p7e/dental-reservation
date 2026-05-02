# Proposal: Create Database Seeders

## What

Create the baseline database seeders for the dental reservation system. These seeders populate the data that must exist before the application can function — roles, loyalty tiers, and the initial admin account:

- `RoleSeeder` — creates the three application roles (`patient`, `doctor`, `admin`) using `spatie/laravel-permission`
- `LoyaltyTierSeeder` — seeds the `loyalty_tiers` table with the three tiers and their discount bonus percentages
- `AdminUserSeeder` — creates one admin user from `.env` credentials and assigns the `admin` role
- `DatabaseSeeder` — orchestrates the above three seeders in the correct dependency order

All seeders are idempotent (safe to re-run on an existing database).

## Why

The application cannot operate correctly without this baseline data:

- **Roles must exist** before any user can be assigned a role or before gate/policy checks can work
- **Loyalty tiers must exist** before a `LoyaltyAccount` can be evaluated for tier upgrades and discount application
- **An admin account must exist** to bootstrap the system — there is no self-registration path for admins, so a seeded admin is the only entry point to the admin panel

Seeding this data separately from demo/factory data keeps the production bootstrap clean and repeatable. Running `php artisan db:seed` in any environment (local, staging, production) produces exactly the same baseline state.

## Non-goals

- Demo/fake data (doctors, services, appointments) — separate change
- Model factories — separate change
- Seeding schedule slots or loyalty transactions — runtime concern, not baseline
