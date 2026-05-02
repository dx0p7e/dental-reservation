## Why

The system has no unified view of what changed, who changed it, and when. Healthcare data mutation — appointment status transitions, loyalty point awards, tier upgrades — needs traceability for GDPR-adjacent compliance and academic thesis rigour around data integrity. The `Appointment` model already emits activity log entries via `spatie/laravel-activitylog`, but loyalty models are uncovered and there is no admin UI to query the log.

## What Changes

- Add `LogsActivity` trait to `LoyaltyTransaction` and `LoyaltyAccount`, tracking the attributes that carry business meaning (`points_delta`, `type` on transactions; `points_balance`, `tier` on accounts)
- Create a read-only Filament `ActivityLogResource` that lets admins browse, filter, and inspect all activity log entries across all subjects
- **Note**: `spatie/laravel-activitylog` is already installed and `Appointment` already uses it. A custom implementation would conflict with the existing integration — this change leverages the existing infrastructure consistently.

## Capabilities

### New Capabilities

- `audit-log-view`: Read-only Filament admin resource for browsing activity log entries, filterable by event type and subject model

### Modified Capabilities

<!-- None — adding traits to loyalty models is an implementation detail; no spec-level requirement changes to existing capabilities -->

## Impact

- `app/Models/LoyaltyTransaction.php` — adds `LogsActivity` trait
- `app/Models/LoyaltyAccount.php` — adds `LogsActivity` trait
- `app/Filament/Resources/` — new `ActivityLogResource` (read-only, no mutations)
- No schema migration required (the `activity_log` table is already created by the spatie package migration)
