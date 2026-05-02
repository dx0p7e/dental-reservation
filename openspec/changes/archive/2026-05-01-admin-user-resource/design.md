## Context

The admin panel (`app/Filament/Resources/`) has resources for doctors, services, appointments, schedules, loyalty tiers/rules, and activity logs. Every resource follows the same structural pattern: a slim `XxxResource.php` that delegates form schema to `Schemas/XxxForm`, table schema to `Tables/XxxTable`, and houses relation managers in `RelationManagers/`. Page classes (`Create`, `Edit`, `List`) are thin — custom logic lives in `afterCreate()` / `afterSave()` hooks.

The `User` model uses a `role` string enum (`patient` | `doctor` | `admin`), a `HasRoles` trait (Spatie Permission), and one-to-one relationships to `Doctor` (via `user_id`) and `LoyaltyAccount` (via `patient_id`). The `Doctor` model requires only `user_id` plus `specialization` (optional) — a minimal row is sufficient to unblock doctor-panel access.

There is currently no way for admins to manage the user account layer: roles, verification flags, passwords, or loyalty data.

## Goals / Non-Goals

**Goals:**
- Full CRUD on user accounts from the admin panel
- Role assignment synced to both the `role` enum column and Spatie `model_has_roles`
- Admin verification overrides (email, phone) without re-sending verification flows
- Password reset by admin without requiring the current password
- Read-only visibility into appointments and loyalty account / transaction history
- Doctor profile auto-creation guard: assigning `doctor` role always ensures a `Doctor` row exists
- Self-deletion guard: admin cannot delete their own account

**Non-Goals:**
- Impersonation
- User activity timeline (already covered by `ActivityLogResource`)
- Bulk role assignment
- Email address change with re-verification
- Any changes to the patient SPA, `DoctorResource`, or registration flow

## Decisions

### 1. Follow the existing extracted-schema pattern

**Decision:** `UserResource` delegates form schema to `Users/Schemas/UserForm`, table schema to `Users/Tables/UsersTable`, and keeps the resource class thin — identical to `DoctorResource`.

**Rationale:** All existing resources follow this pattern. Consistency reduces cognitive load and keeps the resource class readable.

**Alternative considered:** Inline schema methods. Rejected — they grow unwieldy and deviate from the project convention.

---

### 2. Password field on create only — no separate password form section on edit

**Decision:** The create page shows the `password` field. The edit page omits it. Password changes on edit are performed exclusively via the `resetPassword` table/header action (modal with new password + confirmation).

**Rationale:** This is the standard Filament pattern for sensitive fields. It avoids accidental password overwrites during routine edits and keeps the modal intent explicit.

**Alternative considered:** Password in edit form behind a toggle. Rejected — more complex, and the modal approach is clearer for admins.

---

### 3. Role sync in `afterCreate()` / `afterSave()` hooks

**Decision:** The `CreateUser` and `EditUser` page classes override `afterCreate()` and `afterSave()` respectively. On create: `$user->assignRole($data['role'])`. On edit: `$user->syncRoles([$this->data['role']])` plus update the `role` enum column if changed.

**Rationale:** Spatie roles are stored in a pivot table — they cannot be written via Eloquent `fill()` alone. Decoupling the sync into lifecycle hooks keeps the form schema clean.

**Alternative considered:** Custom `mutateFormDataBeforeCreate()` + observer. Rejected — hooks are simpler, co-located, and fully explicit.

---

### 4. Doctor profile auto-creation in the same hooks

**Decision:** In both `afterCreate()` and `afterSave()`, after role sync: if the selected role is `doctor` and `Doctor::where('user_id', $user->id)->doesntExist()`, create a minimal `Doctor` record (`is_active: true`, empty `specialization` and `bio`).

**Rationale:** A doctor user without a `Doctor` row causes a null-crash on the doctor panel. Auto-creating the row is safe and idempotent (checked with `doesntExist()`).

---

### 5. Verified-at toggles on the form rather than actions-only

**Decision:** The form has two boolean toggles (`email_verified_at` and `phone_verified_at`). `mutateFormDataBeforeFill()` maps the column to a boolean; `mutateFormDataBeforeSave()` maps it back to `now()` / `null`.

**Rationale:** Inline toggles are simpler than dedicated modal actions for create and edit flows. The row actions (`verifyEmail`, `verifyPhone`, `revokeVerifications`) are additive — they let admins change status from the table without entering edit mode.

---

### 6. Relation managers are strictly read-only

**Decision:** `AppointmentsRelationManager` and `LoyaltyAccountRelationManager` expose no `headerActions`, `recordActions`, or `toolbarActions` — identical to the existing `PatientsRelationManager` pattern.

**Rationale:** Appointments and loyalty data are managed through their own resources and the booking flow. Editing them directly on the user page would bypass business rules (e.g., loyalty transaction integrity, slot availability).

---

### 7. `canDelete()` guard — no self-deletion

**Decision:** Override `canDelete(Model $record): bool` on `UserResource` to return `auth()->id() !== $record->id`.

**Rationale:** Prevents an admin from accidentally deleting their own account and losing panel access.

## Risks / Trade-offs

- **Role enum vs Spatie out of sync** — Both the `role` column and `model_has_roles` are updated in hooks. If one fails (e.g., DB error mid-request), they could diverge. Mitigation: wrap both writes in a DB transaction in the hook.
- **Bulk `verifyEmails` on large tables** — If many users are selected, the bulk action runs N individual `update()` calls. Mitigation: this is an admin-only panel and user counts are small; a single `whereIn` update is used in the bulk action implementation.
- **`resetPassword` modal has no strength enforcement** — Only `min:8` validation is applied (matches the existing registration rule). Mitigation: consistent with the rest of the app; not a new regression.

## Migration Plan

No database migrations are required. The resource reads existing columns and tables (`users`, `model_has_roles`, `doctors`, `loyalty_accounts`, `loyalty_transactions`, `appointments`).

Deploy steps:
1. Run `php artisan filament:cache-components` after deployment if component caching is enabled
2. No rollback steps needed — removing the resource class is sufficient to revert
