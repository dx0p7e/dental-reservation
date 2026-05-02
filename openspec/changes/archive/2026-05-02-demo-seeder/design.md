## Context

The app has three existing idempotent seeders: `RoleSeeder`, `LoyaltyTierSeeder`, and `AdminUserSeeder`, all called from `DatabaseSeeder`. Demo data has been entered manually — that data is being cleared before the thesis defence. The new seeders must produce a deterministic, realistic dataset that demonstrates every major feature: booking, loyalty tiers, appointment history, doctor portal, and admin panel.

## Goals / Non-Goals

**Goals:**
- 7 new seeder classes under `database/seeders/`, orchestrated by `DemoSeeder`
- Single entry point: `php artisan db:seed --class=DemoSeeder`
- Idempotent: safe to re-run (`firstOrCreate` / `updateOrCreate` throughout)
- Realistic display data for thesis demo — named people, real Lithuanian phone numbers, plausible appointment dates
- Loyalty accounts show varied tiers (Standard → Gold) without needing real earning history volume

**Non-Goals:**
- No migration changes
- No factory changes or factory usage in seeders (explicit data only)
- No changes to the existing `DatabaseSeeder` call chain (DemoSeeder is registered as an additional callable class, not replacing existing seeders)
- No email sending — seeded appointments bypass notification events

## Decisions

### 1. Tier set explicitly, not derived from points balance

The `LoyaltyTier` thresholds are `silver=500`, `gold=1500` points. The user-specified demo balances (Jonas 125 pts / Silver, Rūta 210 pts / Gold) are intentionally below those thresholds — they're chosen to look realistic on screen, not to satisfy the promotion algorithm.

**Decision:** `LoyaltySeeder` sets `tier` directly on the `LoyaltyAccount` record (`updateOrCreate`). The `points_balance` reflects demo-plausible earned points. The `tier` field is treated as an override for demo purposes.

**Alternative considered:** Seed enough appointments to legitimately earn tier-qualifying points — rejected because it would require 50+ appointment records and make the dataset unwieldy.

### 2. Direct model creation in LoyaltySeeder, no service layer

The proposal references `LoyaltyService::recordEarn()` — this method does not exist. The actual service (`LoyaltyPricingService`) handles pricing previews, not point recording. Point transactions in production are recorded by event listeners on appointment completion.

**Decision:** `LoyaltySeeder` creates `LoyaltyAccount` and `LoyaltyTransaction` records directly via model `create()`. Each completed appointment in `AppointmentSeeder` gets a corresponding `earn` transaction. `points_balance` on the account is set to the sum of those transactions.

**Alternative considered:** Firing `AppointmentCompleted` events in the seeder — rejected to avoid unintended side effects (emails, notifications) in CI / seed runs.

### 3. Seeder call order in DemoSeeder

Dependency order:
```
ServiceSeeder            (no deps)
DoctorSeeder             (needs Services for pivot)
PatientSeeder            (no deps beyond User/Role)
DoctorScheduleSeeder     (needs Doctors)
SlotSeeder               (needs Doctors; does NOT read DoctorSchedule — see decision 4)
AppointmentSeeder        (needs Patients, Doctors, Slots, Services)
LoyaltySeeder            (needs Appointments to link transactions)
```
`DemoSeeder::run()` calls them in this order.

### 4. Slot generation strategy

`SlotSeeder` seeds **last 30 days + next 14 days** of Mon–Fri slots per doctor using fixed 30-min blocks from 09:00–17:00 (~64 weekdays × 16 slots × 3 doctors ≈ 3,072 rows). This window provides enough past slots for `AppointmentSeeder` to link completed appointments to, without requiring a separate "past slots" seeder.

**Dashboard "this month" consideration:** The admin dashboard defaults to `this_month` period. The `RevenueEstimateWidget` and `AppointmentsOverviewWidget` both filter by `COALESCE(schedule_slots.date, …)`. `AppointmentSeeder` links all `completed` appointments to slots dated within the current calendar month — this ensures the admin dashboard shows non-zero stats immediately on first login without requiring the presenter to change the period filter.

**Note on DoctorSchedule:** `DoctorScheduleSeeder` seeds 5 weekday rows per doctor (Mon–Fri, 09:00–17:00, 30-min slots) so that the doctor portal "My Schedule" panel is populated. `SlotSeeder` does NOT read these rows — it iterates dates explicitly. This keeps SlotSeeder deterministic and independent of schedule setup order.

**Alternative considered:** Using `SlotGenerationService` — rejected because it reads `DoctorSchedule` records that may not exist, and we want deterministic output with no schedule setup dependency.

### 5. Doctor user accounts

Each doctor gets a `User` (role `doctor`, verified email, password `password`) and a linked `Doctor` record. Email format: `firstname.lastname@klinika.lt` using lowercase ASCII transliteration (e.g., `marta.kazlauskiene@klinika.lt`).

### 6. `slot_type` field in `schedule_slots`

The `schedule_slots` table has `slot_type enum('self', 'request')` with no default — the column is NOT NULL. All slots seeded by `SlotSeeder` represent publicly bookable time blocks.

**Decision:** Set `slot_type = 'self'` on every row created by `SlotSeeder`. The `'request'` variant (patient submits a booking request that admin/doctor must confirm) is exercised by the `requestStore` appointment flow in production — no need to seed request-type slots separately.

## Risks / Trade-offs

- **Tier / points mismatch** → Mitigated by decision 1: tier is set explicitly; the mismatch is intentional and documented
- **Re-running on a partially seeded DB** → All seeders use `firstOrCreate`/`updateOrCreate` keyed on unique fields (email, name); safe to re-run
- **Slot date drift** → Slots are seeded relative to `now()` (next 14 days). If the DB is seeded today and demoed in 3 weeks, all "future" slots will have passed. Mitigation: re-seed just before the demo (`php artisan db:seed --class=DemoSeeder`)
- **Appointment date/time conflicts** → `AppointmentSeeder` picks slots by index rather than random selection to stay deterministic across runs

## Migration Plan

1. Clear existing manual data (already in progress by user)
2. Run `php artisan db:seed --class=DemoSeeder`
3. Verify via admin panel and patient logins

No rollback needed — re-running the seeder is the recovery path.

## Open Questions

- None — all decisions are resolved above (including the `slot_type` NOT NULL gap and missing `DoctorScheduleSeeder` surface identified during explore)
