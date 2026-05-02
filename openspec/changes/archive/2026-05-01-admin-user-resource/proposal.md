## Why

Admins have no way to manage user accounts from the panel. There is no `UserResource` — user-adjacent management exists only in `DoctorResource` (which covers doctor *profiles*, not the underlying user accounts). Admins cannot assign roles, override verification status, reset passwords, or inspect loyalty status for any user.

## What Changes

- **New `UserResource`** at `app/Filament/Admin/Resources/UserResource.php` — full CRUD for user accounts in the admin panel
- **Table view** with columns for name, email (with verified badge), phone (with verified badge), role badge, loyalty tier badge, loyalty points balance, and created at; filters for role, email/phone verified status, and loyalty tier; global search on name and email
- **Create form** with name, email, password (create only), role select, phone, email/phone verified toggles, and notification channel; on save: assigns Spatie role; if role is `doctor`, auto-creates a minimal `Doctor` profile row
- **Edit form** — same as create minus password; role change syncs Spatie role via `syncRoles()` and updates the enum column; if new role is `doctor` and no `Doctor` profile exists, creates one
- **Row actions**: `resetPassword` (modal, no current password required), `verifyEmail`, `verifyPhone`, `revokeVerifications` (confirmation modal)
- **Bulk actions**: `verifyEmails`, `deleteSelected` (with self-deletion guard)
- **Relation managers**: `AppointmentsRelationManager` (read-only), `LoyaltyAccountRelationManager` (read-only, with transaction history)
- **Self-deletion guard**: `canDelete()` returns `false` when `auth()->id() === $record->id`

## Capabilities

### New Capabilities

- `admin-user-management`: Admin CRUD for user accounts — role assignment, verification overrides, password reset, loyalty status visibility, doctor profile auto-creation on role assignment

### Modified Capabilities

<!-- No existing spec-level requirements are changing -->

## Impact

- New Filament resource class and two relation managers
- No changes to patient SPA, registration flow, or `DoctorResource`
- No new migrations — reads existing `users`, `model_has_roles`, `loyalty_accounts`, `appointments`, and `doctors` tables
- `Doctor` model may be created as a side-effect of role assignment (reuses existing factory-compatible defaults)
