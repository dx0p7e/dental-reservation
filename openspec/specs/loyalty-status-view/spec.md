## ADDED Requirements

### Requirement: The loyalty API response includes next-tier progress and transaction history
The `GET /api/v1/loyalty` endpoint SHALL return `next_tier` (the name of the next tier above the patient's current balance, or `null` if at the highest tier), `points_to_next_tier` (the number of points needed to reach the next tier, or `null` if at the highest tier), and a `transactions` array containing the patient's earn history ordered newest-first.

#### Scenario: Response includes next tier data when patient is not at the highest tier
- **WHEN** an authenticated patient requests `GET /api/v1/loyalty` and their balance is below the highest tier threshold
- **THEN** the response includes a non-null `next_tier` string and a positive integer `points_to_next_tier`

#### Scenario: Response returns null next-tier fields when patient is at the highest tier
- **WHEN** an authenticated patient requests `GET /api/v1/loyalty` and their balance meets or exceeds the highest `LoyaltyTier.points_threshold`
- **THEN** `next_tier` is `null` and `points_to_next_tier` is `null`

#### Scenario: Response returns null next-tier fields when no LoyaltyTier rows exist
- **WHEN** an authenticated patient requests `GET /api/v1/loyalty` and no `LoyaltyTier` rows are seeded
- **THEN** `next_tier` is `null` and `points_to_next_tier` is `null` and no error is raised

#### Scenario: Response includes transaction history
- **WHEN** an authenticated patient has earn transactions and requests `GET /api/v1/loyalty`
- **THEN** the `transactions` array contains one entry per transaction with `id`, `type`, `points_delta`, `service_name`, and `created_at`, ordered newest-first

#### Scenario: Transactions with no associated appointment return null service_name
- **WHEN** a transaction exists without a linked appointment
- **THEN** the transaction entry's `service_name` field is `null`

### Requirement: LoyaltyView displays progress toward the next tier
The patient loyalty page SHALL show a progress bar indicating how many points the patient has earned toward the next tier threshold. When the patient is at the highest tier, the progress bar SHALL be replaced with a congratulatory max-tier message.

#### Scenario: Progress bar renders when patient is below the highest tier
- **WHEN** the loyalty API returns a non-null `next_tier`
- **THEN** the page shows a progress bar with a label indicating points needed to reach the next tier

#### Scenario: Max-tier congratulatory state renders when patient is at the highest tier
- **WHEN** the loyalty API returns `next_tier: null`
- **THEN** the progress bar is hidden and a "Gold member" (or equivalent) congratulatory message is shown

### Requirement: LoyaltyView displays the patient's transaction history
The patient loyalty page SHALL display a list of earn transactions, showing the date, service name, and points earned for each entry.

#### Scenario: Transaction list is shown when earn history exists
- **WHEN** the loyalty API returns a non-empty `transactions` array
- **THEN** the page displays each transaction with its date, service name (or a fallback for null), and formatted points earned

#### Scenario: Empty state is shown when no transactions exist
- **WHEN** the loyalty API returns an empty `transactions` array
- **THEN** the page shows a message indicating no transactions yet
