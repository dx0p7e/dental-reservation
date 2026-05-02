## 1. Custom Dashboard Page

- [x] 1.1 Create `app/Filament/Pages/Dashboard.php` extending `Filament\Pages\Dashboard` with `HasFiltersForm` trait
- [x] 1.2 Implement `filtersForm(Schema $schema)` returning a `Select::make('period')` with options: `this_month` / `last_month` / `last_30_days` / `all_time` and default `this_month`
- [x] 1.3 Update `AdminPanelProvider`: replace `Dashboard::class` in `pages()` with `App\Filament\Pages\Dashboard::class`; remove `FilamentInfoWidget::class` from `widgets()`

## 2. Appointments Overview Widget

- [x] 2.1 Create `app/Filament/Widgets/AppointmentsOverviewWidget.php` extending `StatsOverviewWidget`, using `InteractsWithPageFilters`
- [x] 2.2 Implement a `dateRange()` helper that returns a `[Carbon $start, Carbon $end]` pair based on `$this->pageFilters['period']` (default `this_month`); handle all four options
- [x] 2.2a Extract `dateRange()` to `app/Filament/Concerns/HasPeriodFilter.php` trait; use the trait in both `AppointmentsOverviewWidget` and `RevenueEstimateWidget`
- [x] 2.3 Implement `getStats()` returning stat cards: Total, Pending, Confirmed, Completed, Cancelled — each querying via `Appointment::leftJoin('schedule_slots', ...)` and `whereBetween(DB::raw('COALESCE(schedule_slots.date, appointments.preferred_date, DATE(appointments.created_at))'), $range)`
- [x] 2.4 Register `AppointmentsOverviewWidget::class` in `AdminPanelProvider::widgets()`

## 3. Loyalty Distribution Widget

- [x] 3.1 Create `app/Filament/Widgets/LoyaltyDistributionWidget.php` extending `StatsOverviewWidget`
- [x] 3.2 Implement `getStats()` returning stat cards for Standard, Silver, and Gold — counting `LoyaltyAccount::where('tier', ...)` (no date filter applied)
- [x] 3.3 Register `LoyaltyDistributionWidget::class` in `AdminPanelProvider::widgets()`

## 4. Revenue Estimate Widget

- [x] 4.1 Create `app/Filament/Widgets/RevenueEstimateWidget.php` extending `StatsOverviewWidget`, using `InteractsWithPageFilters`
- [x] 4.2 Implement `getStats()` using `Appointment::join('services', ...)->leftJoin('schedule_slots', ...)->where('status', Completed)->whereBetween(DB::raw('COALESCE(schedule_slots.date, appointments.preferred_date, DATE(appointments.created_at))'), dateRange())` summing `services.price`; format as currency; add description "List price only — discounts not applied"
- [x] 4.3 Register `RevenueEstimateWidget::class` in `AdminPanelProvider::widgets()`

## 5. Tests

- [x] 5.1 Test `AppointmentsOverviewWidget` — seed known appointments within and outside the period, assert stat values match expected counts
- [x] 5.2 Test `RevenueEstimateWidget` — seed completed appointments with known service prices, assert revenue total is correct
- [x] 5.3 Test custom Dashboard page renders without error for an admin

## 6. Code Style

- [x] 6.1 Run `vendor/bin/pint --dirty --format agent`
