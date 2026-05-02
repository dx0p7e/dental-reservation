## Requirements

### Requirement: Slot generation action warns when no new slots are created

When a schedule's slot generation action produces zero new slots, the system SHALL show a warning notification rather than a success notification.

#### Scenario: Generation creates zero slots

- **WHEN** the admin runs "Generate Slots" on a schedule and the service returns `created = 0`
- **THEN** a warning notification is shown: "No slots generated"
- **AND** the notification body advises the admin to check the schedule configuration
- **AND** no success notification is shown

#### Scenario: Generation creates one or more slots

- **WHEN** the admin runs "Generate Slots" and the service returns `created > 0`
- **THEN** a success notification is shown with the count of created slots
- **AND** no warning notification is shown
