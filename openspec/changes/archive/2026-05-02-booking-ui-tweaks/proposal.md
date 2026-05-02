## Why

The slot-selection step of the booking flow (`/doctors/{id}/slots`) becomes hard to use when many slots are available: the "Continue" button is buried below a long scroll, and all slots across multiple days are merged into a single undifferentiated grid that is difficult to scan.

## What Changes

- The two-column layout (sidebar | slot grid + continue) is replaced with a **three-column layout**: left sidebar (service select + date filter), centre slot grid, right sticky column (continue button + hint text). Both the left sidebar and right continue panel are sticky so they stay in view while the user scrolls through slots.
- Slot cards in the centre column are **grouped by day** under a labelled date heading, so each day's slots appear as a visually distinct block rather than one continuous grid.

## Capabilities

### New Capabilities
- none

### Modified Capabilities
- `spa-booking-ui`: The layout of `DoctorSlotsView` changes from two columns to three; slot cards are now rendered grouped by date; sidebar and continue panel are sticky.

## Impact

- `resources/spa/views/DoctorSlotsView.vue` — template restructure (three-column layout, sticky panels, day-grouped slot rendering)
- `openspec/specs/spa-booking-ui/spec.md` — requirements updated to reflect new layout and grouping rules
- No backend changes, no API changes, no store changes
