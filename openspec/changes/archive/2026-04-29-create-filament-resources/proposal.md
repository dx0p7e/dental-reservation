# Proposal: Create Filament Resources

## What

Create four Filament v4 admin panel Resources in `app/Filament/Resources`, each providing full CRUD (index table + create form + edit form):

| Resource | Model | Navigation Group |
|---|---|---|
| `DoctorResource` | `Doctor` | Staff |
| `ServiceResource` | `Service` | Configuration |
| `LoyaltyTierResource` | `LoyaltyTier` | Configuration |
| `LoyaltyRuleResource` | `LoyaltyRule` | Configuration |

## Why

The Filament panel (`/admin`) exists but has no Resources yet — admins can log in but see only the dashboard widgets. The core reference data for the dental reservation system (doctors, services, loyalty configuration) needs to be manageable through the admin UI before appointment workflows can be built on top of it.

## Scope

**Included:**
- Full CRUD for each Resource (table list, create, edit)
- `DoctorResource` — user relationship via BelongsTo select (existing User records)
- `ServiceResource` — all scalar fields + complexity enum select
- `LoyaltyTierResource` — tier enum select, threshold, discount bonus
- `LoyaltyRuleResource` — service BelongsTo select, points, discount, validity
- Navigation grouping: Doctor under "Staff", remaining three under "Configuration"

**Excluded:**
- Custom pages (e.g., appointment calendar) — come with the appointments change
- Custom dashboard widgets — separate change
- Filament Relations Managers (e.g., appointment history on Doctor) — future change
- Any changes to existing models or migrations

## Constraints

- Filament v4.11.1 is already installed
- `AdminPanelProvider` is configured with `discoverResources(in: app_path('Filament/Resources'), ...)` — resources are auto-discovered
- All Resources are PHP 8.4 / Laravel 13 code
- Tests must be written and pass before this change is complete
