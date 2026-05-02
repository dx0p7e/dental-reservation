# Tasks: Patient Loyalty Status View

## 1. API — LoyaltyController

- [x] 1.1 In `LoyaltyController@show`, replace `$request->user()->loyaltyAccount` with a constrained eager load
  ```php
  $request->user()->loyaltyAccount()->with([
      'transactions' => fn($q) => $q->latest()->with('appointment.service'),
  ])->first()
  ```
  Note: chaining `->latest()` on the outer `loyaltyAccount()` query orders `loyalty_accounts` rows, not transactions — use the constrained form above.

## 2. API — LoyaltyResource

- [x] 2.1 At the top of `toArray()`, compute `$next` once: `$next = LoyaltyTier::where('points_threshold', '>', $this->points_balance)->orderBy('points_threshold')->first();` — then return `next_tier => $next?->tier` and `points_to_next_tier => $next ? $next->points_threshold - $this->points_balance : null` in the array. Do not query `LoyaltyTier` twice.
- [x] 2.2 Add `transactions` field: map `$this->whenLoaded('transactions', ...)` — each entry: `{ id, type, points_delta, service_name: $tx->appointment?->service?->name, created_at: $tx->created_at->toDateString() }`, ordered newest-first

## 3. Frontend — TypeScript types

- [x] 3.1 In `resources/spa/types/index.ts`, extend `LoyaltyAccount` interface to add `next_tier: string | null`, `points_to_next_tier: number | null`, and `transactions: LoyaltyTransaction[]`
- [x] 3.2 Add new `LoyaltyTransaction` interface: `{ id: number; type: string; points_delta: number; service_name: string | null; created_at: string }`

## 4. Frontend — LoyaltyView.vue

- [x] 4.1 Add progress bar section: shown when `loyalty.next_tier !== null`; compute `percent = (loyalty.points_balance / (loyalty.points_balance + loyalty.points_to_next_tier!)) * 100`; display bar with label "X pts to `next_tier`"
- [x] 4.2 Add max-tier state: shown when `loyalty.next_tier === null`; display a congratulatory "Gold member" message in place of the progress bar
- [x] 4.3 Add transaction history list below the status card; iterate `loyalty.transactions`; each row shows `created_at`, `service_name ?? 'Manual adjustment'`, and `+points_delta pts`
- [x] 4.4 Add empty-transactions state: shown when `loyalty.transactions.length === 0`; display "No transactions yet"

## 5. Tests

- [x] 5.1 Run `php artisan make:test --pest LoyaltyStatusTest --no-interaction`
- [x] 5.2 Test: `GET /api/v1/loyalty` returns `next_tier` and `points_to_next_tier` when patient is below the highest tier
- [x] 5.3 Test: `GET /api/v1/loyalty` returns `null` for both next-tier fields when patient is at or above the highest tier
- [x] 5.4 Test: `GET /api/v1/loyalty` returns `null` for both next-tier fields when no `LoyaltyTier` rows exist
- [x] 5.5 Test: `GET /api/v1/loyalty` response includes `transactions` array with correct shape
- [x] 5.6 Test: `GET /api/v1/loyalty` returns `service_name: null` for transactions without an appointment

## 6. Code Style

- [x] 6.1 Run `vendor/bin/pint app/Http/Resources/Api/V1/LoyaltyResource.php app/Http/Controllers/Api/V1/LoyaltyController.php --format agent`
