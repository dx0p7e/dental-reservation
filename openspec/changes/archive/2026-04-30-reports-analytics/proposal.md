## Why

The admin panel has full data on appointments, loyalty transactions, and services but no aggregated view of any of it. Admins must manually count or query to understand basic operational metrics. Adding a dashboard widget layer delivers an immediate operational picture for the thesis demo and satisfies FR16.

## What Changes

- Create `app/Filament/Pages/Dashboard.php` — custom dashboard page extending the Filament base, adding a `HasFiltersForm` date-range selector (This Month / Last Month / Last 30 Days / All Time)
- Create `app/Filament/Widgets/AppointmentsOverviewWidget.php` — stats widget showing total appointments and per-status breakdown for the selected period
- Create `app/Filament/Widgets/LoyaltyDistributionWidget.php` — stats widget showing patient counts per loyalty tier (Standard / Silver / Gold)
- Create `app/Filament/Widgets/RevenueEstimateWidget.php` — stats widget showing sum of `services.price` for Completed appointments in the selected period, labelled as an estimate
- Update `AdminPanelProvider` to register the three new widgets and point `pages` to the custom Dashboard

## Capabilities

### New Capabilities

- `admin-dashboard`: Filterable admin dashboard with appointment overview, loyalty tier distribution, and revenue estimate widgets

### Modified Capabilities

<!-- None — no existing spec-level behavior changes -->

## Impact

- `app/Filament/Pages/Dashboard.php` — new file (custom dashboard page)
- `app/Filament/Widgets/` — three new widget files
- `app/Providers/Filament/AdminPanelProvider.php` — register new page and widgets, remove `FilamentInfoWidget`
- No migrations required — all queries use existing `appointments`, `services`, `loyalty_accounts` tables
- No API changes
