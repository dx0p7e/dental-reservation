## Context

All loyalty tables (`loyalty_accounts`, `loyalty_transactions`, `loyalty_tiers`, `loyalty_rules`) and their Eloquent models exist and are seeded, but nothing writes to them during normal app flow. `AppointmentStatus::Completed` is the natural trigger point for earning — it's the authoritative signal that a service was delivered.

The existing `UserObserver` (registered in `AppServiceProvider::boot()`) already demonstrates the observer pattern in this codebase. `LoyaltyAccount` has a unique-per-patient constraint, and `points_balance` is an unsigned integer. `LoyaltyTier` rows map tier names (`standard`, `silver`, `gold`) to `points_threshold` values.

## Goals / Non-Goals

**Goals:**
- Award `LoyaltyRule.points_earned` points whenever an appointment transitions to `Completed`
- Record the earning as a `LoyaltyTransaction` (type `earn`)
- Recalculate and update `LoyaltyAccount.tier` after each award
- Be a no-op when no `LoyaltyRule` exists for the appointment's service
- Be idempotent-safe: only fire on the `Completed` transition, not on re-saves of already-completed appointments

**Non-Goals:**
- Point redemption, expiry, or refund on cancellation
- Creating `LoyaltyAccount` rows here (handled by `UserObserver`)
- Filament UI for loyalty transactions in this change

## Decisions

### 1 — Observer vs. Event/Listener

**Decision:** Use an `AppointmentObserver` with an `updated()` hook.

**Rationale:** The codebase already uses observers (`UserObserver`). An observer keeps the side-effect co-located with the model without coupling the Filament resource to loyalty logic. A dedicated `AppointmentCompleted` event/listener would be more decoupled but adds two files and a service provider binding for a single trigger — premature for now.

**Guard clause:** Inside `updated()`, check `$appointment->wasChanged('status') && $appointment->status === AppointmentStatus::Completed` to ensure the logic fires only on the status transition, not on arbitrary re-saves.

### 2 — Tier recalculation strategy

**Decision:** Before calling `increment('points_balance', $n)`, compute `$newBalance = $account->points_balance + $rule->points_earned` from the in-memory model (since `increment()` issues a direct `UPDATE` and does not refresh the model). Then query `LoyaltyTier::where('points_threshold', '<=', $newBalance)->orderByDesc('points_threshold')->value('tier')` inside the same `DB::transaction` to find the highest applicable tier, and update `LoyaltyAccount.tier` only if it changed.

**Rationale:** `LoyaltyTier` has at most 3 rows; a single ordered query is cheap and avoids in-memory sorting. Storing tier on the account (denormalized) keeps display reads simple without a join.

**Alternative considered:** Always recalculate by loading all tiers into a collection and using `->filter()->last()` — equivalent cost, slightly less readable.

### 3 — DB::transaction scope

**Decision:** Wrap the `LoyaltyTransaction` insert and the `increment` + tier update in a single `DB::transaction`.

**Rationale:** The transaction and account balance must stay consistent. A failed tier update after a successful insert would leave the account in a valid (if slightly stale) state, but wrapping both prevents any partial writes.

### 4 — Missing LoyaltyAccount handling

**Decision:** Use `LoyaltyAccount::firstOrCreate(['patient_id' => $appointment->patient_id], ['points_balance' => 0, 'tier' => 'standard'])` rather than assuming the account always exists.

**Rationale:** `UserObserver` creates accounts only for users with `role === 'patient'`. If a user was created before that observer was registered, or role assignment happened after creation, the account may be absent. `firstOrCreate` is safe and idempotent.

## Risks / Trade-offs

- **Double-award risk** — If an appointment is saved twice with `status = completed` (e.g. two concurrent requests), both would pass `wasChanged('status')` if the first write hasn't flushed. Mitigation: the `LoyaltyTransaction` table has no unique constraint preventing duplicates. For now, this is an accepted edge case; a unique index on `(appointment_id, type = 'earn')` would close it but is out of scope.
- **Observer not firing in artisan/seeder context** — `DB::transaction`-wrapped bulk inserts or `updateOrCreate` calls in seeders bypass observers. Mitigation: document that seeds should not set status to `Completed` unless intentional.
- **`LoyaltyTier` table empty** — If no tiers are seeded, the tier query returns `null` and the update is skipped gracefully; the account stays on `standard`.

## Open Questions

*(none — all design decisions are resolved)*
