## 1. Model Coverage

- [x] 1.1 Add `LogsActivity` to `LoyaltyTransaction`: `logOnly(['points_delta', 'type'])->logOnlyDirty()->dontLogEmptyChanges()`
- [x] 1.2 Add `LogsActivity` to `LoyaltyAccount`: `logOnly(['points_balance', 'tier'])->logOnlyDirty()->dontLogEmptyChanges()`

## 2. Filament Resource

- [x] 2.1 Create `app/Filament/Resources/ActivityLogResource.php` — read-only resource wrapping `Spatie\Activitylog\Models\Activity`; `canCreate`, `canEdit`, `canDelete` all return `false`
- [x] 2.2 Add table columns: `created_at` (sortable, default sort desc), `event` (badge: created=success, updated=warning, deleted=danger), `subject_type` + `subject_id` (combined — use `->formatStateUsing(fn($state) => class_basename($state))` on the subject_type column to strip the namespace, e.g. `App\Models\LoyaltyAccount` → `LoyaltyAccount`), causer name (resolves `causer->name` or "System"), `description`
- [x] 2.3 Add table filters: `SelectFilter` for `event` (created / updated / deleted), `SelectFilter` for `subject_type` (Appointment, LoyaltyTransaction, LoyaltyAccount)
- [x] 2.4 Set resource navigation label, icon (`heroicon-o-shield-check`), and group (`System`)

## 3. Tests

- [x] 3.1 Test `LoyaltyTransaction` create writes an activity log entry with correct event and attributes
- [x] 3.2 Test `LoyaltyAccount` tier/balance update writes an activity log entry recording old and new values
- [x] 3.3 Test `ActivityLogResource` page renders without error for an admin user in Filament

## 4. Code Style

- [x] 4.1 Run `vendor/bin/pint --dirty --format agent`
