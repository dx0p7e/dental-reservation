# Tasks: Create Filament Resources

## Implementation Tasks

### T1 — Scaffold DoctorResource
- [x] Run `php artisan make:filament-resource Doctor --generate`
- [x] Open `app/Filament/Resources/DoctorResource.php`
- [x] Add `->navigationGroup('Staff')` and `->navigationIcon('heroicon-o-user-group')`
- [x] Replace table columns with spec: `user.name` (sortable+searchable), `user.email` (searchable), `specialization` (sortable+searchable), `is_active` (IconColumn boolean), `created_at` (date, sortable)
- [x] Add `SelectFilter` on `is_active` to table filters
- [x] Replace form fields with spec: `user_id` Select with `relationship('user', 'name')` + `searchable()`, `specialization` TextInput required, `bio` Textarea nullable, `is_active` Toggle default true

---

### T2 — Scaffold ServiceResource
- [x] Run `php artisan make:filament-resource Service --generate`
- [x] Open `app/Filament/Resources/ServiceResource.php`
- [x] Add `->navigationGroup('Configuration')` and `->navigationIcon('heroicon-o-wrench-screwdriver')`
- [x] Replace table columns with spec: `name` (sortable+searchable), `duration_minutes` (suffix: `min`, sortable), `price` (money prefix `€`, sortable), `complexity` (BadgeColumn, simple=gray / complex=warning, sortable)
- [x] Replace form fields with spec: `name` TextInput required, `description` Textarea nullable, `duration_minutes` numeric TextInput (suffix `min`) required, `price` numeric TextInput (prefix `€`) required, `complexity` Select (simple/complex) required

---

### T3 — Scaffold LoyaltyTierResource
- [x] Run `php artisan make:filament-resource LoyaltyTier --generate`
- [x] Open `app/Filament/Resources/LoyaltyTierResource.php`
- [x] Add `->navigationGroup('Configuration')` and `->navigationIcon('heroicon-o-star')`
- [x] Replace table columns with spec: `tier` (BadgeColumn, standard=gray / silver=info / gold=warning, sortable), `points_threshold` (numeric, sortable), `discount_bonus_pct` (suffix `%`, sortable)
- [x] Replace form fields with spec: `tier` Select (standard/silver/gold) required, `points_threshold` numeric TextInput required, `discount_bonus_pct` numeric TextInput (suffix `%`) required

---

### T4 — Scaffold LoyaltyRuleResource
- [x] Run `php artisan make:filament-resource LoyaltyRule --generate`
- [x] Open `app/Filament/Resources/LoyaltyRuleResource.php`
- [x] Add `->navigationGroup('Configuration')` and `->navigationIcon('heroicon-o-gift')`
- [x] Replace table columns with spec: `service.name` (sortable+searchable), `points_earned` (numeric, sortable), `discount_pct` (suffix `%`, sortable), `valid_months` (suffix `mo`, nullable, sortable)
- [x] Replace form fields with spec: `service_id` Select with `relationship('service', 'name')` + `searchable()` required, `points_earned` numeric TextInput required, `discount_pct` numeric TextInput (suffix `%`) required, `valid_months` numeric TextInput (suffix `mo`) nullable

---

### T5 — Run Pint on all generated files
- [x] Run `vendor/bin/pint app/Filament/ --format agent` to fix style issues

---

### T6 — Write tests for DoctorResource
- [x] Run `php artisan make:test --pest Filament/DoctorResourceTest`
- [x] Write tests: list page renders, create page renders, can create a Doctor, edit page renders, can edit a Doctor
- [x] Run tests and confirm they pass: `php artisan test --compact --filter=DoctorResourceTest`

---

### T7 — Write tests for ServiceResource
- [x] Run `php artisan make:test --pest Filament/ServiceResourceTest`
- [x] Write tests: list page renders, create page renders, can create a Service, edit page renders, can edit a Service
- [x] Run tests and confirm they pass: `php artisan test --compact --filter=ServiceResourceTest`

---

### T8 — Write tests for LoyaltyTierResource
- [x] Run `php artisan make:test --pest Filament/LoyaltyTierResourceTest`
- [x] Write tests: list page renders, create page renders, can create a LoyaltyTier, edit page renders, can edit a LoyaltyTier
- [x] Run tests and confirm they pass: `php artisan test --compact --filter=LoyaltyTierResourceTest`

---

### T9 — Write tests for LoyaltyRuleResource
- [x] Run `php artisan make:test --pest Filament/LoyaltyRuleResourceTest`
- [x] Write tests: list page renders, create page renders, can create a LoyaltyRule, edit page renders, can edit a LoyaltyRule
- [x] Run tests and confirm they pass: `php artisan test --compact --filter=LoyaltyRuleResourceTest`

---

### T10 — Full regression test
- [x] Run `php artisan test --compact` and confirm all previously passing tests still pass (no regressions)
