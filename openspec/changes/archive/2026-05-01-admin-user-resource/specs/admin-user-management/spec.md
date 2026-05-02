## ADDED Requirements

### Requirement: Admin can list and search users
The system SHALL provide a paginated table of all users in the admin panel, with global search on name and email, and filters for role, email verified status, phone verified status, and loyalty tier.

#### Scenario: Table displays user attributes
- **WHEN** an admin visits the Users resource index
- **THEN** the table shows name, email with verified badge, phone with verified badge, role badge (colour-coded), loyalty tier badge, loyalty points balance, and created-at date for each user

#### Scenario: Admin filters by role
- **WHEN** an admin selects a role filter (e.g., "patient")
- **THEN** only users with that role are shown

#### Scenario: Admin searches by name or email
- **WHEN** an admin types in the global search box
- **THEN** the table filters to users whose name or email matches the search term

---

### Requirement: Admin can create a user account
The system SHALL allow an admin to create a new user with name, email, password, role, phone, email-verified toggle, phone-verified toggle, and notification channel.

#### Scenario: Create patient user
- **WHEN** an admin fills all required fields, selects role "patient", and submits the create form
- **THEN** a new User record is created, the Spatie role "patient" is assigned, and the admin is redirected to the edit page

#### Scenario: Create doctor user — Doctor profile auto-created
- **WHEN** an admin creates a user with role "doctor"
- **THEN** the system creates a `Doctor` row for that user (`is_active = true`, empty `specialization` and `bio`) if one does not already exist

#### Scenario: Email verified toggle on create
- **WHEN** an admin enables the "Mark email as verified" toggle before saving
- **THEN** `email_verified_at` is set to the current timestamp

#### Scenario: Email NOT verified when toggle is off
- **WHEN** the "Mark email as verified" toggle is off
- **THEN** `email_verified_at` is `null`

---

### Requirement: Admin can edit a user account
The system SHALL allow an admin to edit name, email, role, phone, email-verified toggle, phone-verified toggle, and notification channel. Password is NOT editable directly on the edit form.

#### Scenario: Role change syncs Spatie roles
- **WHEN** an admin changes a user's role and saves
- **THEN** the `role` enum column is updated AND the user's Spatie roles are synced (old role removed, new role assigned)

#### Scenario: Doctor profile auto-created on role change
- **WHEN** an admin changes a user's role to "doctor" and no `Doctor` row exists for that user
- **THEN** a minimal `Doctor` record is created automatically

#### Scenario: Edit form does not show password field
- **WHEN** an admin opens the edit page for any user
- **THEN** there is no password input field visible

---

### Requirement: Admin can reset a user's password via modal action
The system SHALL provide a "Reset Password" row action on the Users table and edit page header that opens a modal with new password and confirmation fields. No current password is required.

#### Scenario: Successful password reset
- **WHEN** an admin fills in a new password (min 8 chars) and matching confirmation, then submits
- **THEN** the user's password is updated and the modal closes with a success notification

#### Scenario: Password mismatch rejected
- **WHEN** the new password and confirmation do not match
- **THEN** the form shows a validation error and the password is not changed

---

### Requirement: Admin can override email and phone verification status
The system SHALL provide `verifyEmail`, `verifyPhone`, and `revokeVerifications` row actions on the Users table.

#### Scenario: Verify email action sets verified-at
- **WHEN** an admin clicks "Verify Email" for a user whose email is not verified
- **THEN** `email_verified_at` is set to the current timestamp

#### Scenario: Verify email action hidden when already verified
- **WHEN** the user's email is already verified
- **THEN** the "Verify Email" action is not visible in the row actions

#### Scenario: Revoke verifications requires confirmation
- **WHEN** an admin clicks "Revoke Verifications"
- **THEN** a confirmation modal is shown before both `email_verified_at` and `phone_verified_at` are set to `null`

---

### Requirement: Admin can bulk-verify email addresses
The system SHALL provide a "Verify Emails" bulk action on the Users table that sets `email_verified_at = now()` for all selected users.

#### Scenario: Bulk verify emails
- **WHEN** an admin selects multiple users and triggers "Verify Emails"
- **THEN** `email_verified_at` is set to the current timestamp for all selected users

---

### Requirement: Admin cannot delete their own account
The system SHALL prevent an admin from deleting the currently authenticated user's account.

#### Scenario: Self-deletion is blocked
- **WHEN** an admin attempts to delete their own user record (via row action or bulk delete)
- **THEN** the delete action is hidden / disabled for that record

#### Scenario: Deleting other users is allowed
- **WHEN** an admin deletes a different user
- **THEN** the record is deleted normally

---

### Requirement: Admin can view a user's appointments (read-only)
The system SHALL show a read-only list of a user's appointments on the user edit page via a relation manager.

#### Scenario: Appointments relation manager is read-only
- **WHEN** an admin views the Appointments tab on a user's edit page
- **THEN** appointment rows are displayed (status, service, doctor, date, final price) with no create, edit, or delete actions available

---

### Requirement: Admin can view a user's loyalty account (read-only)
The system SHALL show a read-only loyalty account summary (tier, points balance, transaction history) on the user edit page via a relation manager.

#### Scenario: Loyalty account tab displays current state
- **WHEN** an admin views the Loyalty Account tab on a patient's edit page
- **THEN** the tier, points balance, and recent transactions are displayed with no edit actions available

#### Scenario: Loyalty tab absent for non-patient users
- **WHEN** the user has no `loyaltyAccount` (e.g., role is admin or doctor)
- **THEN** the loyalty relation manager shows an empty state without errors
