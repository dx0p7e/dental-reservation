## Context

The Filament admin panel is configured in `AdminPanelProvider`. Currently it registers the base `Filament\Pages\Dashboard` class (no customisation) and two default widgets: `AccountWidget` and `FilamentInfoWidget`. There are no custom widgets or pages under `app/Filament/Widgets/` or `app/Filament/Pages/` — both directories do not yet exist.

Dashboard filtering in Filament v4 requires a custom Dashboard page that uses the `HasFiltersForm` trait. Widgets that need the filter values use `InteractsWithPageFilters` to access `$this->pageFilters`.

Data sources:
- **Appointments**: `appointments` table, joined via `service` relationship for price. Status enum: `Pending`, `Confirmed`, `Completed`, `Cancelled`, `NoShow`.
- **Services price**: `services.price` decimal column.
- **Loyalty tiers**: `loyalty_accounts.tier` string column (`standard`, `silver`, `gold`).

## Goals / Non-Goals

**Goals:**

- Three widgets on the default `/admin` dashboard: appointment status breakdown, loyalty tier distribution, revenue estimate
- A date-range selector on the dashboard allowing admins to switch between This Month / Last Month / Last 30 Days / All Time
- Widgets re-render reactively when the filter changes

**Non-Goals:**

- CSV export
- Per-doctor breakdowns
- Trend charts over time
- Patient-level drill-down
- Separate reports route or resource

## Decisions

**Decision 1 — Select filter, not date pickers**

The FR16 description calls for preset date ranges ("This Month / Last Month / Last 30 Days / All Time"), not free-form start/end dates. A `Select` component in `filtersForm()` with those four options is simpler, less error-prone, and better for a thesis demo than two `DatePicker` fields.

*Alternative considered*: Two `DatePicker` fields for arbitrary ranges. Rejected — more complex to validate, doesn't match the described scope.

---

**Decision 2 — `HasFiltersForm` (inline form), not `HasFiltersAction` (modal)**

The inline form keeps the filter visible at all times, which is clearer in a demo context.

*Alternative considered*: `HasFiltersAction` (filter inside a modal triggered by a button). Rejected — the filter is a core part of the dashboard experience and should not be hidden behind a button.

---

**Decision 3 — `StatsOverviewWidget` for all three widgets**

Filament v4 base class is `Filament\Widgets\StatsOverviewWidget`. All three widgets are stat cards — no chart or table widget is needed. `Stat::make(label, value)` with an optional `->description()` is sufficient for the thesis scope.

---

**Decision 4 — Revenue is labelled "estimate"**

`services.price` is a list-price field. No discount calculation is applied. The widget label and description make this explicit ("Estimated revenue — list price only").

---

**Decision 5 — Remove `FilamentInfoWidget` from panel**

`FilamentInfoWidget` displays Filament version info, which is irrelevant in a demo. It will be removed from `AdminPanelProvider` as part of this change. `AccountWidget` remains.

---

**Decision 6 — COALESCE date resolution for period filtering, not `created_at`**

Filtering on `appointments.created_at` produces "booking month" semantics — a December booking for a January appointment shows in December's revenue. The correct operational metric is the appointment *occurrence* date. FR14 introduced two appointment populations (slot-based and request-based), so the date source varies:

- `slot_id NOT NULL` → use `schedule_slots.date` (joined via LEFT JOIN)
- `slot_id IS NULL`, `preferred_date NOT NULL` → use `preferred_date`
- Neither set → fall back to `DATE(appointments.created_at)`

Query pattern:
```sql
LEFT JOIN schedule_slots ON schedule_slots.id = appointments.slot_id
WHERE COALESCE(schedule_slots.date, appointments.preferred_date, DATE(appointments.created_at))
  BETWEEN :start AND :end
```

*Alternative considered*: INNER JOIN + `whereNotNull('slot_id')`. Rejected — silently excludes all request-based appointments introduced in FR14, undermining the thesis argument that both booking flows are first-class.

*Alternative considered*: `created_at` only. Rejected — "booking month" is semantically wrong for revenue attribution.

**Decision 7 — Shared `HasPeriodFilter` trait for `dateRange()` logic**

`dateRange()` is needed by both `AppointmentsOverviewWidget` and `RevenueEstimateWidget`. Extracting it to `app/Filament/Concerns/HasPeriodFilter.php` avoids duplication and ensures both widgets resolve periods identically.

## Risks / Trade-offs

- [N+1 on revenue query] → Use a single JOIN query (`appointments JOIN services WHERE status=Completed AND date filter`), not lazy-loading per appointment.
- [Filter default state] → On first load, `pageFilters['period']` may be null; widgets default to "This Month" behaviour if the filter is unset.
- [Tier names are lowercase strings, not an Enum] → Query uses raw string values (`standard`, `silver`, `gold`) — consistent with the existing `loyalty_accounts.tier` column.
