# Tasks: Admin UserResource

## 1. Scaffold `UserResource` structure

- [x] 1.1 Run `php artisan make:filament-resource User --no-interaction` and move output to `app/Filament/Resources/Users/` following the existing `Doctors/` directory pattern (resource class, Pages subdir, Schemas subdir, Tables subdir, RelationManagers subdir)
- [x] 1.2 Create `app/Filament/Resources/Users/Schemas/UserForm.php` with a static `configure(Schema $schema): Schema` method (empty for now)
- [x] 1.3 Create `app/Filament/Resources/Users/Tables/UsersTable.php` with a static `configure(Table $table): Table` method (empty for now)
- [x] 1.4 Wire `UserResource::form()` → `UserForm::configure($schema)` and `UserResource::table()` → `UsersTable::configure($table)`
- [x] 1.5 Set `$model = User::class`, `$navigationIcon = 'heroicon-o-users'`, `$navigationGroup = 'Users'`, `$navigationSort = 1`, `$recordTitleAttribute = 'name'`, `$globalSearchSearchableColumns = ['name', 'email']`

## 2. Table columns and filters (`UsersTable`)

- [x] 2.1 Add `TextColumn::make('name')` — sortable, searchable
- [x] 2.2 Add `TextColumn::make('email')` with an `IconColumn` or badge suffix for `email_verified_at` (green check if not null, red "Nepatvirtintas" label if null) — use `formatStateUsing` + `icon`/`color` helpers or a `BadgeColumn`
- [x] 2.3 Add `TextColumn::make('phone')` with equivalent verified badge for `phone_verified_at`
- [x] 2.4 Add `BadgeColumn` (or `TextColumn::badge()`) for `role` — colours: `admin` = `danger`, `doctor` = `warning`, `patient` = `primary`
- [x] 2.5 Add `TextColumn::make('loyaltyAccount.tier')` — badge, label "Loyalty Tier"; show "—" when null
- [x] 2.6 Add `TextColumn::make('loyaltyAccount.points_balance')` — label "Taškai", numeric, default "—" when null
- [x] 2.7 Add `TextColumn::make('created_at')` — date, sortable
- [x] 2.8 Add `SelectFilter::make('role')` with options `['patient' => 'Patient', 'doctor' => 'Doctor', 'admin' => 'Admin']`
- [x] 2.9 Add `TernaryFilter` (or `SelectFilter`) for `email_verified_at` — queries `whereNotNull`/`whereNull`
- [x] 2.10 Add `TernaryFilter` for `phone_verified_at` — same pattern
- [x] 2.11 Add `SelectFilter::make('loyaltyAccount.tier')` — options `['standard', 'silver', 'gold']` — uses `whereHas`

## 3. Create form (`UserForm` — create page context)

- [x] 3.1 Add `TextInput::make('name')` — required, max 255
- [x] 3.2 Add `TextInput::make('email')` — required, email, unique validation (`Rule::unique('users','email')->ignore($record?->id)`)
- [x] 3.3 Add `TextInput::make('password')` — required on create (`hiddenOn('edit')`), min 8, password type, confirmed
- [x] 3.4 Add `Select::make('role')` — required, options `['patient', 'doctor', 'admin']`, default `'patient'`
- [x] 3.5 Add `TextInput::make('phone')` — nullable, E.164 format validation
- [x] 3.6 Add `Toggle::make('email_verified_at')` — label "Mark email as verified"; `formatStateUsing(fn($s) => $s !== null)`; do NOT use `dehydrateStateUsing` — timestamp conversion is handled in page-level `mutateFormDataBeforeCreate` / `mutateFormDataBeforeSave` hooks (see T4.2 and T5.2)
- [x] 3.7 Add `Toggle::make('phone_verified_at')` — same pattern as T3.6
- [x] 3.8 Add `Select::make('notification_channel')` — options `['email' => 'Email', 'sms' => 'SMS', 'both' => 'Both']`, default `'email'`

## 4. Create page hooks (`CreateUser`)

- [x] 4.1 Create `app/Filament/Resources/Users/Pages/CreateUser.php` extending `CreateRecord`
- [x] 4.2 Override `mutateFormDataBeforeCreate(array $data): array` — convert toggle booleans to timestamps: `$data['email_verified_at'] = $data['email_verified_at'] ? now() : null` and `$data['phone_verified_at'] = $data['phone_verified_at'] ? now() : null`; return `$data`
- [x] 4.3 Override `afterCreate()`: wrap in `DB::transaction` — call `$this->record->assignRole($this->data['role'])` then, if role is `doctor`, call `Doctor::firstOrCreate(['user_id' => $this->record->id], ['is_active' => true, 'specialization' => '', 'bio' => ''])`

## 5. Edit page hooks (`EditUser`)

- [x] 5.1 Create `app/Filament/Resources/Users/Pages/EditUser.php` extending `EditRecord`
- [x] 5.2 Override `mutateFormDataBeforeFill(array $data): array` — map timestamps to booleans: `$data['email_verified_at'] = isset($data['email_verified_at'])`; `$data['phone_verified_at'] = isset($data['phone_verified_at'])`; return `$data`
- [x] 5.3 Override `mutateFormDataBeforeSave(array $data): array` — convert booleans back to timestamps while preserving the existing timestamp when already verified: `$data['email_verified_at'] = $data['email_verified_at'] ? ($this->record->email_verified_at ?? now()) : null`; same for `phone_verified_at`; return `$data`
- [x] 5.4 Override `afterSave()`: wrap in `DB::transaction` — call `$this->record->syncRoles([$this->data['role']])`, update `role` column if changed (`$this->record->update(['role' => $this->data['role']])`), then auto-create Doctor profile if new role is `doctor` and none exists
- [x] 5.5 Add `resetPassword` header action: `Action::make('resetPassword')` with modal form (`TextInput password` required min 8 + `TextInput password_confirmation` required same), `action(fn($data) => $this->record->update(['password' => $data['password']]))`
- [x] 5.6 Create `app/Filament/Resources/Users/Pages/ListUsers.php` extending `ListRecords` with `CreateAction` header action

## 6. Row actions (table)

- [x] 6.1 Add `Action::make('verifyEmail')` — sets `email_verified_at = now()`, hidden when `$record->email_verified_at !== null`
- [x] 6.2 Add `Action::make('verifyPhone')` — sets `phone_verified_at = now()`, hidden when `$record->phone_verified_at !== null`
- [x] 6.3 Add `Action::make('revokeVerifications')` — requires confirmation, sets both `email_verified_at` and `phone_verified_at` to `null`
- [x] 6.4 Add standard `EditAction` and `DeleteAction` (with self-guard — see section 8)

## 7. Bulk actions

- [x] 7.1 Add `BulkAction::make('verifyEmails')` — `User::whereIn('id', $records->pluck('id'))->update(['email_verified_at' => now()])`
- [x] 7.2 Add `DeleteBulkAction` — filter out `auth()->id()` from the selected records before deleting (prevents self-deletion)

## 8. Self-deletion guard

- [x] 8.1 Override `canDelete(Model $record): bool` on `UserResource` to return `auth()->id() !== $record->id`

## 9. Relation managers

- [x] 9.1 Create `app/Filament/Resources/Users/RelationManagers/AppointmentsRelationManager.php` — `$relationship = 'appointments'`; table columns: `service.name`, `doctor.user.name`, `slot.date` (date), `slot.start_time` (time), `status` (badge), `final_price`; no `headerActions`, `recordActions`, or `toolbarActions`
- [x] 9.2 Add `loyaltyTransactions(): HasManyThrough` relationship to `User` model: `return $this->hasManyThrough(LoyaltyTransaction::class, LoyaltyAccount::class, 'patient_id', 'loyalty_account_id')` — required for the relation manager below
- [x] 9.3 Do NOT create a `LoyaltyAccountRelationManager` — `loyaltyAccount` is a `HasOne` and Filament relation managers require `HasMany`/`HasManyThrough`/`BelongsToMany`. Instead: (a) add a disabled `Section` to the edit form (via `UserForm`) showing `loyaltyAccount.tier` and `loyaltyAccount.points_balance` as read-only `TextInput` fields; (b) create `app/Filament/Resources/Users/RelationManagers/LoyaltyTransactionsRelationManager.php` with `$relationship = 'loyaltyTransactions'`; columns: `created_at` (date), `type` (badge), `points_delta`; no write actions
- [x] 9.4 Register `AppointmentsRelationManager` and `LoyaltyTransactionsRelationManager` in `UserResource::getRelations()`

## 10. Tests

- [x] 10.1 Create `tests/Feature/UserResourceTest.php` (Pest)
- [x] 10.2 Test: table renders and lists users (admin auth required)
- [x] 10.3 Test: create form creates user, assigns Spatie role
- [x] 10.4 Test: creating a doctor user auto-creates a Doctor profile
- [x] 10.5 Test: edit form role change syncs Spatie role and updates `role` enum column
- [x] 10.6 Test: edit doctor role change creates Doctor profile if missing
- [x] 10.7 Test: `resetPassword` action updates the password hash
- [x] 10.8 Test: `verifyEmail` action sets `email_verified_at`
- [x] 10.9 Test: `revokeVerifications` action clears both verified-at columns
- [x] 10.10 Test: `canDelete()` returns `false` for the authenticated admin's own record

## 11. Code style

- [x] 11.1 Run `vendor/bin/pint app/Filament/Resources/Users/ tests/Feature/UserResourceTest.php --format agent`
