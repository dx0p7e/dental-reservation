## MODIFIED Requirements

### Requirement: LoyaltyView renders a status card with tier, points, and progress bar
The system SHALL render `LoyaltyView.vue` with a `<PageHeader title="Your Loyalty Status" />` followed by a top card containing:
- A tier badge: `Standard` → `bg-gray-100 text-gray-600`, `Silver` → `bg-slate-100 text-slate-700`, `Gold` → `bg-amber-100 text-amber-700`
- The `points_balance` value in `text-5xl font-bold text-clinic-text`
- A progress bar filled with `bg-clinic-teal` when `next_tier` is non-null
- A "Maximum tier reached" label in `text-clinic-teal` when `next_tier` is null

The `transactions` field from the loyalty API response MAY be absent (when the patient has no loyalty account). The template SHALL default to an empty array (`loyalty.transactions ?? []`) for all list and length accesses, preventing a "Cannot read properties of undefined (reading 'length')" crash.

The `LoyaltyAccount` TypeScript interface SHALL declare `transactions` as `LoyaltyTransaction[] | undefined` to reflect the API's conditional serialisation via `whenLoaded`.

#### Scenario: LoyaltyView renders when patient has no loyalty account (transactions absent)
- **WHEN** the loyalty API returns a response with no `transactions` key (fallback account, relation not loaded)
- **THEN** LoyaltyView renders without throwing a JavaScript error
- **THEN** the "No transactions yet." empty state is shown

#### Scenario: LoyaltyView renders when patient has an account with transactions
- **WHEN** the loyalty API returns a response with a populated `transactions` array
- **THEN** each transaction is rendered with its date, description, and `+N pts` / `−N pts` value

#### Scenario: LoyaltyView renders when patient has an account with zero transactions
- **WHEN** the loyalty API returns `transactions: []`
- **THEN** the "No transactions yet." empty-state message is displayed

## ADDED Requirements

### Requirement: LoyaltyController always serialises a transactions relation
The system SHALL ensure that `LoyaltyController::show` sets an empty `transactions` relation on any fallback `LoyaltyAccount` instance so that `LoyaltyResource::whenLoaded` always finds a loaded (possibly empty) collection and always includes the `transactions` key in the JSON response.

#### Scenario: Patient has no loyalty account
- **WHEN** `$request->user()->loyaltyAccount()` returns null
- **THEN** the controller creates a fallback `LoyaltyAccount` with `points_balance: 0`, `tier: 'standard'`, and an empty `transactions` relation
- **THEN** the API response includes `"transactions": []`
