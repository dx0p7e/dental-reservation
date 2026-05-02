## ADDED Requirements

### Requirement: Completing an appointment automatically awards loyalty points to the patient
When an appointment's status transitions to `Completed`, the system SHALL look up the `LoyaltyRule` for the appointment's service. If a rule exists, the system SHALL create a `LoyaltyTransaction` of type `earn` and increment the patient's `LoyaltyAccount.points_balance` by `LoyaltyRule.points_earned`, wrapped in a single database transaction.

#### Scenario: Points are awarded when appointment is marked Completed
- **WHEN** an appointment's status is updated to `Completed` and a `LoyaltyRule` exists for the appointment's service
- **THEN** a `LoyaltyTransaction` record is created with `type = 'earn'`, `points_delta = rule.points_earned`, and the correct `loyalty_account_id` and `appointment_id`
- **AND** the patient's `LoyaltyAccount.points_balance` is incremented by `rule.points_earned`

#### Scenario: No points are awarded when no LoyaltyRule exists for the service
- **WHEN** an appointment's status is updated to `Completed` and no `LoyaltyRule` exists for the appointment's service
- **THEN** no `LoyaltyTransaction` is created and `LoyaltyAccount.points_balance` is unchanged

#### Scenario: Points are NOT awarded on re-saves of already-completed appointments
- **WHEN** an already-`Completed` appointment is saved again without changing its status
- **THEN** no additional `LoyaltyTransaction` is created

#### Scenario: Points are NOT awarded when status changes to a non-Completed value
- **WHEN** an appointment's status changes from `Pending` to `Confirmed`
- **THEN** no `LoyaltyTransaction` is created

### Requirement: Loyalty account tier is recalculated after points are awarded
After `points_balance` is incremented, the system SHALL compare the new balance against `LoyaltyTier.points_threshold` values and update `LoyaltyAccount.tier` to the highest tier the patient qualifies for.

#### Scenario: Tier is upgraded when balance crosses a threshold
- **WHEN** a patient's `points_balance` after earning crosses the `silver` tier threshold
- **THEN** `LoyaltyAccount.tier` is updated to `silver`

#### Scenario: Tier is unchanged when balance does not cross any new threshold
- **WHEN** a patient earns points but their balance stays below the next threshold
- **THEN** `LoyaltyAccount.tier` remains unchanged

#### Scenario: Tier recalculation is skipped when LoyaltyTier table is empty
- **WHEN** no `LoyaltyTier` rows exist and points are awarded
- **THEN** `LoyaltyAccount.tier` remains `standard` and no error is raised

### Requirement: LoyaltyAccount is created automatically if missing when points are awarded
The system SHALL use `firstOrCreate` to ensure a patient's `LoyaltyAccount` exists before recording points, starting with `points_balance = 0` and `tier = 'standard'`.

#### Scenario: Account is created on first award if it does not exist
- **WHEN** an appointment is completed for a patient who has no `LoyaltyAccount`
- **THEN** a new `LoyaltyAccount` is created with `points_balance = rule.points_earned` and the appropriate tier
