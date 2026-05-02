## Why

Patients can now earn loyalty points (Change 13), but the existing `/api/v1/loyalty` endpoint returns only `{ points_balance, tier }` and `LoyaltyView.vue` renders just the number with a tier badge. There is no progress bar, no next-tier target, and no transaction history — the data is there but it surfaces no motivation to keep booking.

## What Changes

- **`LoyaltyResource`** — extend response to include `next_tier` (string|null), `points_to_next_tier` (int|null), and a `transactions` array with `{ id, type, points_delta, service_name, created_at }`. Computed server-side: query `LoyaltyTier` rows to find the next tier above current balance.
- **`LoyaltyController@show`** — add eager-loading of `transactions.appointment.service` so the resource can include transaction history without N+1 queries.
- **`LoyaltyView.vue`** — add a progress bar toward the next tier (or a "Gold member" max-tier state), and a transaction history list showing date, service, and points earned.
- **TypeScript `LoyaltyAccount` type** — extend to include `next_tier`, `points_to_next_tier`, and a `transactions` array.

> **Codebase note:** `GET /api/v1/loyalty` already exists. The SPA route `/loyalty` and `LoyaltyView.vue` already exist. No new routes, controllers, or Vue page files are needed — this is purely an enhancement. The proposed `role === patient` middleware check is not needed and does not exist in this codebase; the endpoint is already guarded by `auth:sanctum`.

## Capabilities

### New Capabilities

- `loyalty-status-view`: Patient-facing loyalty status display — progress toward next tier, full transaction history.

### Modified Capabilities

*(no existing spec-level loyalty display requirements change; the prior endpoint returned only a balance and was not formally spec'd)*

## Impact

- `app/Http/Resources/Api/V1/LoyaltyResource.php` — add `next_tier`, `points_to_next_tier`, `transactions`
- `app/Http/Controllers/Api/V1/LoyaltyController.php` — add eager-loading
- `resources/spa/views/LoyaltyView.vue` — progress bar, max-tier state, transaction list
- `resources/spa/types/index.ts` — extend `LoyaltyAccount` interface
- No new migrations, routes, or dependencies
