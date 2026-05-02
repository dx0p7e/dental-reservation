## ADDED Requirements

### Requirement: LoyaltyView renders a status card with tier, points, and progress bar
The system SHALL render `LoyaltyView.vue` with a `<PageHeader title="Your Loyalty Status" />` followed by a top card containing:
- A tier badge: `Standard` → `bg-gray-100 text-gray-600`, `Silver` → `bg-slate-100 text-slate-700`, `Gold` → `bg-amber-100 text-amber-700`
- Points balance displayed prominently (`text-4xl font-bold text-clinic-text`)
- A progress bar (`bg-clinic-teal` fill on `bg-clinic-border` track) showing progress toward the next tier
- Next-tier label: "{N} points to {next_tier}" in `text-clinic-muted`, or "Maximum tier reached" when `next_tier` is null

#### Scenario: Status card shows tier badge and points
- **WHEN** the patient navigates to `/loyalty`
- **THEN** the current tier badge and points balance are visible in the status card

#### Scenario: Progress bar shows progress toward next tier
- **WHEN** the patient has a non-null `next_tier`
- **THEN** a teal progress bar is shown with the correct fill proportion and a label showing points remaining

#### Scenario: Maximum tier shows no progress bar
- **WHEN** `next_tier` is null
- **THEN** no progress bar is rendered and the label "Maximum tier reached" is shown

### Requirement: LoyaltyView renders a transaction history section
The system SHALL render a "Transaction History" section heading below the status card, followed by a list of `LoyaltyTransaction` rows each showing: date (`created_at`), service name (or transaction type if no service name), and points delta formatted as `+N pts` in `text-clinic-teal` for positive deltas or `−N pts` in `text-red-500` for negative.

#### Scenario: Transactions are listed
- **WHEN** the patient has loyalty transactions
- **THEN** each transaction row shows the date, description, and coloured points delta

#### Scenario: Empty transaction history
- **WHEN** the patient has no transactions
- **THEN** an empty-state message "No transactions yet" is shown in the transaction history section
