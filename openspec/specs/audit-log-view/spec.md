### Requirement: Admin can browse the activity log

The system SHALL provide a read-only Filament admin resource that lists all entries from the `activity_log` table. Each row SHALL display the timestamp, event type (created / updated / deleted), subject model class and record ID, causer name (or "System" when null), and the log description.

#### Scenario: Admin views activity log index

- **WHEN** an admin navigates to the activity log resource in the Filament panel
- **THEN** the table lists activity log entries ordered by most recent first

#### Scenario: Admin filters by event type

- **WHEN** an admin applies an event filter (created, updated, or deleted)
- **THEN** only entries matching the selected event type are shown

#### Scenario: Admin filters by subject model type

- **WHEN** an admin selects a subject model (Appointment, LoyaltyTransaction, LoyaltyAccount)
- **THEN** only entries for that subject type are shown

#### Scenario: Causer is null (system-triggered event)

- **WHEN** an activity log entry has no causer (e.g., triggered by a scheduled command)
- **THEN** the causer column displays "System"

### Requirement: Activity log is immutable in the admin panel

The system SHALL NOT expose create, edit, or delete actions on the activity log resource. The log MUST be append-only from an admin UI perspective.

#### Scenario: Admin cannot mutate log entries

- **WHEN** an admin views the activity log resource
- **THEN** no create, edit, or delete buttons are visible or accessible

### Requirement: Loyalty model changes are audited

The system SHALL create an activity log entry whenever a `LoyaltyTransaction` or `LoyaltyAccount` record is created, updated, or deleted, recording the changed attributes and the authenticated user (causer) who triggered the change.

#### Scenario: Loyalty transaction created (points awarded)

- **WHEN** a `LoyaltyTransaction` is created
- **THEN** an activity log entry exists with `event = created` and a snapshot of `points_delta` and `type`

#### Scenario: Loyalty transaction deleted (manual reversal)

- **WHEN** a `LoyaltyTransaction` is deleted
- **THEN** an activity log entry exists with `event = deleted`

#### Scenario: Loyalty account balance changes

- **WHEN** a `LoyaltyAccount`'s `points_balance` is updated
- **THEN** an activity log entry exists with `event = updated` recording the old and new `points_balance` value

#### Scenario: Loyalty account tier changes

- **WHEN** a `LoyaltyAccount`'s `tier` attribute is updated
- **THEN** an activity log entry exists with `event = updated` recording the old and new `tier` value
