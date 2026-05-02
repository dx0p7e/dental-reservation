## Context

`spatie/laravel-activitylog` is already installed (`composer.json`) and the `activity_log` table migration has been run. `App\Models\Appointment` uses the `LogsActivity` trait, logging `status`, `notes`, and `doctor_notes` on dirty changes. The `activity_log` table uses polymorphic `subject_type`/`subject_id` and `causer_type`/`causer_id` columns populated automatically by the package.

**Causer resolution**: spatie resolves the causer from `auth()->user()` using the configured `default_auth_driver` (currently `null` = current Laravel auth driver). Filament actions use the `web` guard; API requests use the `sanctum` guard — both expose an authenticated user, so causer is populated correctly in both contexts. Scheduled commands (e.g., appointment reminders) run without an authenticated user; causer will be `null`, which is acceptable and the causer columns are nullable by design.

## Goals / Non-Goals

**Goals:**

- Extend activity logging to `LoyaltyTransaction` and `LoyaltyAccount`
- Build a read-only Filament admin resource for browsing the activity log
- Ensure the log is immutable from the UI (no delete/edit actions in Filament)

**Non-Goals:**

- Rolling a custom audit log model or observer — spatie/laravel-activitylog is already installed and in use; a parallel system would produce two audit tables for one application
- Log export to CSV (FR16 concern)
- Configuring a log retention policy (the `clean_after_days: 365` default is sufficient)
- Logging every model in the system

## Decisions

**Decision 1 — Use spatie/laravel-activitylog, not a custom implementation**

*Rationale*: The package is already installed, configured, and partially used (Appointment). Creating a custom `ActivityLog` table alongside it would produce two parallel audit systems. The package's automatic causer resolution, `attribute_changes` JSON diff, and append-only design match all stated requirements.

*Alternative considered*: Custom `AuditLog` model + `AuditObserver`. Rejected because it duplicates infrastructure already in place and adds testing surface for the same problem.

---

**Decision 2 — `LogsActivity` trait on models, not a shared observer class**

*Rationale*: Consistent with how `Appointment` is already instrumented. Keeps logging configuration co-located with the model. The trait hooks into Eloquent's `created`/`updated`/`deleted` events internally.

*Alternative considered*: A shared `AuditObserver` class registered in a service provider. Rejected because it diverges from the established pattern and requires a separate registration point.

---

**Decision 3 — `logOnlyDirty()` + explicit attribute list**

*Rationale*: Avoids noisy entries when unrelated columns update. `LoyaltyTransaction` logs `points_delta` and `type` (effectively immutable after creation — mostly create events in practice). `LoyaltyAccount` logs `points_balance` and `tier` (the meaningful state values that change over time).

---

**Decision 4 — Filament resource is fully read-only**

*Rationale*: Audit logs must be immutable. `canCreate`, `canEdit`, and `canDelete` all return `false`. No `EditAction` or `DeleteAction` in the table.

## Risks / Trade-offs

- [Performance on large `activity_log` table] → Default Filament pagination (25/page) and the existing spatie index on `subject_type`, `causer_id`, `created_at` mitigate this for expected data volumes.
- [Causer is null for system-triggered events (scheduled commands)] → Accepted. The Filament table will display "System" when causer is null.
- [LoyaltyTransaction is immutable after creation in practice] → `logOnlyDirty()` on `updated` is a no-op; only `created` events will produce entries for this model. This is correct behaviour, not a gap.

## Migration Plan

No database schema migration required — the `activity_log` table already exists from the spatie published migration.

Deployment order:
1. Add `LogsActivity` traits to loyalty models (zero downtime)
2. Deploy Filament `ActivityLogResource` (zero downtime)
3. Rollback: removing the traits stops new entries; existing entries are unaffected
