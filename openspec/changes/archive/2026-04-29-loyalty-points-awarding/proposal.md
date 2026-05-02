## Why

The loyalty subsystem (tables, models, rules) is fully in place but points are never actually awarded — every appointment completion leaves `loyalty_accounts.points_balance` at 0. This change wires the trigger so the system delivers value the moment it was designed for.

## What Changes

- **New `AppointmentObserver`** — intercepts `updated` events on `Appointment`; when `status` transitions to `Completed`, looks up the `LoyaltyRule` for the appointment's service, and if one exists: creates a `LoyaltyTransaction` (type `earn`), increments `LoyaltyAccount.points_balance`, and recalculates the patient's tier against `LoyaltyTier` thresholds.
- **Observer registration** — `AppointmentObserver` registered in `AppServiceProvider::boot()`, alongside the existing `UserObserver`.
- **No new migrations** — uses the existing `loyalty_transactions`, `loyalty_accounts`, and `loyalty_tiers` tables.
- **No new API routes or Filament resources** — purely backend logic triggered by the existing status change.

## Capabilities

### New Capabilities

- `loyalty-points-awarding`: Automatic awarding of loyalty points when an appointment is completed, including tier recalculation.

### Modified Capabilities

*(none — no existing spec-level requirements change)*

## Impact

- `app/Observers/AppointmentObserver.php` — new file
- `app/Providers/AppServiceProvider.php` — one new `observe()` call in `boot()`
- All loyalty writes are wrapped in `DB::transaction` to prevent partial state
- If no `LoyaltyRule` exists for the service, the observer is a no-op (safe for services without loyalty rules)
- Point redemption, expiry, and reversal on cancellation are explicitly out of scope
