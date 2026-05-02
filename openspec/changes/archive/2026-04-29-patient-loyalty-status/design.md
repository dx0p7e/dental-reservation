## Context

`GET /api/v1/loyalty` returns `{ points_balance, tier }` from `LoyaltyResource`. `LoyaltyController@show` reads from `$request->user()->loyaltyAccount` (a `HasOne` relationship already defined) without any eager-loading. `LoyaltyView.vue` consumes this and renders a balance number with a tier badge. `LoyaltyTier` holds `tier`, `points_threshold`, and `discount_bonus_pct` rows ordered by threshold. Tier enum values are `standard`, `silver`, `gold` (matching `LoyaltyAccount.tier`).

## Goals / Non-Goals

**Goals:**
- Extend the API response to include `next_tier`, `points_to_next_tier`, and an array of `transactions` (earn events only, newest first)
- Eager-load transactions with their service names in one query
- Add a progress bar to `LoyaltyView.vue` for the path to the next tier; show a max-tier state when at gold
- Add a transaction history list to `LoyaltyView.vue`

**Non-Goals:**
- Redemption UI, admin views, or push notifications
- Server-side pagination (transaction volumes per patient are small; full list load is fine)
- New API routes, new Vue pages, new middleware, new migrations
- Splitting into separate `LoyaltyStatusCard.vue` / `LoyaltyTransactionList.vue` components (adding indirection for a small, self-contained view adds no benefit here)

## Decisions

### 1 — Next-tier computation in the Resource, not the Controller

**Decision:** Compute `next_tier` and `points_to_next_tier` inside `LoyaltyResource::toArray()`, querying `LoyaltyTier` there.

**Rationale:** `LoyaltyResource` is already the shape contract. Putting the computation in the Controller would require passing extra data through (or returning an ad-hoc array), which undermines the Resource pattern. A small query inside `toArray()` is clean and localised.

**Alternative considered:** Compute in `LoyaltyController` and pass values to the resource via `additional()`. Equivalent correctness but more coupling.

**Next-tier logic:**
```
$next = LoyaltyTier::where('points_threshold', '>', $this->points_balance)
    ->orderBy('points_threshold')
    ->first();
// next_tier           = $next?->tier (null if at max)
// points_to_next_tier = $next ? $next->points_threshold - $this->points_balance : null
```

### 2 — Transactions included in the loyalty endpoint (not a separate endpoint)

**Decision:** Return `transactions` as a nested array in the existing `GET /api/v1/loyalty` response.

**Rationale:** The Vue view loads both in a single request today. A separate endpoint would require a second `onMounted` fetch, extra loading state, and more error handling for no benefit at these transaction volumes. The SPA already treats the loyalty endpoint as the source of truth for the loyalty section.

**Transaction shape:** `{ id, type, points_delta, service_name, created_at }` — enough for the list view. `service_name` comes from `transaction->appointment->service->name` via eager-loading.

**Edge case:** If a transaction has no associated appointment (future earn types without an appointment), `service_name` returns `null`.

### 3 — Eager loading in the Controller

**Decision:** Change `$request->user()->loyaltyAccount` to `$request->user()->loyaltyAccount()->with('transactions.appointment.service')->first()` in the controller.

**Rationale:** The `HasOne` accessor doesn't accept `with()` arguments; need the relationship method call. The resource will iterate transactions; without eager-loading this is an N+1.

**Fallback when account is null:** Preserve existing behaviour — return a synthetic empty `LoyaltyAccount` (no eager-loading needed for an empty model).

### 4 — Progress bar percentage calculation in the Vue component

**Decision:** Compute the progress bar width client-side: given `points_balance` and `points_to_next_tier` from the API, derive the current tier's threshold from the response (`next_tier_threshold = points_balance + points_to_next_tier`) and calculate `percent = (points_balance / next_tier_threshold) * 100`.

**Rationale:** The API already provides all needed numbers. The server doesn't need to know the current-tier threshold for anything else. Keeps the API response minimal.

## Risks / Trade-offs

- **`LoyaltyTier` table empty** — `$next` query returns `null`; `next_tier` and `points_to_next_tier` both become `null`, which the frontend already needs to handle for the max-tier state anyway. No extra risk.
- **Transaction service_name null** — Transactions without an appointment silently surface `service_name: null`. The Vue list should guard `{{ tx.service_name ?? 'Manual adjustment' }}`.
- **No ordering applied to transactions** — need `->latest()` on the relationship or in the resource so newest appear first in the list.

## Open Questions

*(none)*
