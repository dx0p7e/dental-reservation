# Design: Create Filament Resources

## Architecture

Each Resource follows the standard Filament v4 `Resource` class structure:
- Static `form(Form $form)` — defines the create/edit form
- Static `table(Table $table)` — defines the list table with columns, filters, and row actions
- Static `getRelations()` — empty array (no relation managers in this change)
- Static `getPages()` — `ListXxx`, `CreateXxx`, `EditXxx` pages

Resources live in `app/Filament/Resources/`. Pages live in `app/Filament/Resources/<Name>Resource/Pages/`.

The `AdminPanelProvider` already calls `->discoverResources(in: app_path('Filament/Resources'), ...)` so no manual registration is needed.

---

## Resource Specifications

### DoctorResource

**Navigation:** Group `Staff`, icon `heroicon-o-user-group`

**Table columns:**
| Column | Type | Sortable | Searchable |
|---|---|---|---|
| `user.name` | `TextColumn` | yes | yes |
| `user.email` | `TextColumn` | — | yes |
| `specialization` | `TextColumn` | yes | yes |
| `is_active` | `IconColumn` (boolean) | yes | — |
| `created_at` | `TextColumn` (date) | yes | — |

**Table filters:** `TrashedFilter` not applicable; add `SelectFilter` on `is_active`.

**Form fields:**
| Field | Type | Validation |
|---|---|---|
| `user_id` | `Select` with `relationship('user', 'name')` + searchable | required |
| `specialization` | `TextInput` | required, max:255 |
| `bio` | `Textarea` | nullable, maxLength:1000 |
| `is_active` | `Toggle` | boolean, default true |

---

### ServiceResource

**Navigation:** Group `Configuration`, icon `heroicon-o-wrench-screwdriver`

**Table columns:**
| Column | Type | Sortable | Searchable |
|---|---|---|---|
| `name` | `TextColumn` | yes | yes |
| `duration_minutes` | `TextColumn` (suffix: `min`) | yes | — |
| `price` | `TextColumn` (money: `€`, locale: `lt_LT`) | yes | — |
| `complexity` | `TextColumn` with `->badge()` (simple=gray, complex=warning) | yes | — |

**Form fields:**
| Field | Type | Validation |
|---|---|---|
| `name` | `TextInput` | required, max:255 |
| `description` | `Textarea` | nullable |
| `duration_minutes` | `TextInput` (numeric, suffix: `min`) | required, integer, min:1 |
| `price` | `TextInput` (numeric, prefix: `€`) | required, decimal, min:0 |
| `complexity` | `Select` (options: `simple`, `complex`) | required |

---

### LoyaltyTierResource

**Navigation:** Group `Configuration`, icon `heroicon-o-star`

**Table columns:**
| Column | Type | Sortable |
|---|---|---|
| `tier` | `TextColumn` with `->badge()` (standard=gray, silver=info, gold=warning) | yes |
| `points_threshold` | `TextColumn` (numeric) | yes |
| `discount_bonus_pct` | `TextColumn` (suffix: `%`) | yes |

**Form fields:**
| Field | Type | Validation |
|---|---|---|
| `tier` | `Select` (options: `standard`, `silver`, `gold`) | required |
| `points_threshold` | `TextInput` (numeric) | required, integer, min:0 |
| `discount_bonus_pct` | `TextInput` (numeric, suffix: `%`) | required, decimal, min:0, max:100 |

---

### LoyaltyRuleResource

**Navigation:** Group `Configuration`, icon `heroicon-o-gift`

**Table columns:**
| Column | Type | Sortable | Searchable |
|---|---|---|---|
| `service.name` | `TextColumn` | yes | yes |
| `points_earned` | `TextColumn` (numeric) | yes | — |
| `discount_pct` | `TextColumn` (suffix: `%`) | yes | — |
| `valid_months` | `TextColumn` (suffix: `mo`, nullable) | yes | — |

**Form fields:**
| Field | Type | Validation |
|---|---|---|
| `service_id` | `Select` with `relationship('service', 'name')` + searchable | required |
| `points_earned` | `TextInput` (numeric) | required, integer, min:0 |
| `discount_pct` | `TextInput` (numeric, suffix: `%`) | required, decimal, min:0, max:100 |
| `valid_months` | `TextInput` (numeric, suffix: `mo`) | nullable, integer, min:1 |

---

## File Structure

```
app/Filament/Resources/
├── DoctorResource.php
├── DoctorResource/
│   └── Pages/
│       ├── ListDoctors.php
│       ├── CreateDoctor.php
│       └── EditDoctor.php
├── ServiceResource.php
├── ServiceResource/
│   └── Pages/
│       ├── ListServices.php
│       ├── CreateService.php
│       └── EditService.php
├── LoyaltyTierResource.php
├── LoyaltyTierResource/
│   └── Pages/
│       ├── ListLoyaltyTiers.php
│       ├── CreateLoyaltyTier.php
│       └── EditLoyaltyTier.php
├── LoyaltyRuleResource.php
└── LoyaltyRuleResource/
    └── Pages/
        ├── ListLoyaltyRules.php
        ├── CreateLoyaltyRule.php
        └── EditLoyaltyRule.php
```

## Artisan Generation

Use `php artisan make:filament-resource` to scaffold each resource, then fill in the form/table definitions:

```bash
php artisan make:filament-resource Doctor --generate
php artisan make:filament-resource Service --generate
php artisan make:filament-resource LoyaltyTier --generate
php artisan make:filament-resource LoyaltyRule --generate
```

The `--generate` flag auto-scaffolds columns/fields from the database schema. We then refine them per the specs above.

## Testing Strategy

Use Filament's test helpers (`livewire()` + `InteractsWithFilament`) to test each resource:
- Can render the list page (HTTP 200)
- Can render the create page (HTTP 200)
- Can create a record via the form
- Can render the edit page (HTTP 200)
- Can edit a record via the form

Tests live in `tests/Feature/Filament/` — one test class per resource.

## Key Constraints

- `DoctorResource`: `user_id` select must use `relationship()` so it searches existing User records by name
- `LoyaltyRuleResource`: `service_id` select must use `relationship()` so it searches existing Service records by name
- `LoyaltyTierResource`: `tier` is unique in DB — the create form should not allow duplicate tier values; rely on DB constraint + Filament's default error handling
- No soft deletes on any of these models — no `TrashedFilter`
- All money values in EUR (prefix `€`), locale `lt_LT` (Lithuanian)
- Use `TextColumn->badge()` instead of the deprecated `BadgeColumn` class for all badge columns
