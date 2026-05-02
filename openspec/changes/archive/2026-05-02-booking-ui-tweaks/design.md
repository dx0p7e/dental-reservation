## Context

`DoctorSlotsView.vue` currently uses a two-column layout: a 256 px left sidebar (service select, date filter) and a flex-1 right panel that contains both the slot card grid and the "Continue" button appended below the grid. When a doctor has many available slots across multiple days, users must scroll far down to reach the Continue button, and all slots appear as one undifferentiated grid with no visual separation between days.

## Goals / Non-Goals

**Goals:**
- Move the Continue button (and its hint text) into a dedicated sticky right column so it is always visible without scrolling.
- Make the left sidebar (service select + date filter) sticky too, for consistency.
- Group slot cards under labelled date headings in the centre column.

**Non-Goals:**
- No changes to the booking store, API, routing, or any other view.
- No pagination or virtual scrolling of slots.
- No changes to slot card content (date, time, doctor name remain the same).

## Decisions

### Three-column grid layout
**Decision:** Replace `flex gap-8` with a CSS grid (`grid grid-cols-[16rem_1fr_16rem] gap-8`) so the three columns are fixed-width left and right with a flexible centre.

**Rationale:** CSS grid gives precise column widths without needing nested flex hacks. `grid-cols-[16rem_1fr_16rem]` matches the existing sidebar width and gives the continue panel the same footprint.

**Alternative considered:** Keeping `flex` and adding a third flex child — rejected because controlling the widths cleanly requires extra wrapper divs.

### Sticky sidebar and continue panel
**Decision:** Both the left `<aside>` and the right continue `<aside>` use `sticky top-6 self-start` so they remain in the viewport as the centre scrolls.

**Rationale:** `sticky` + `self-start` is the standard Tailwind pattern for sticky sidebars inside a grid/flex parent. No JS scroll listeners needed.

### Day-grouped slot rendering
**Decision:** Compute a `groupedSlots` computed property — `Map<string, Slot[]>` keyed by `slot.date` — and render it with a `v-for` over the map entries, outputting a date heading followed by a sub-grid of cards per day.

**Rationale:** Keeps the template declarative. Using a `Map` preserves insertion order (dates are already sorted by the API). A `computed` avoids re-grouping on every render.

**Date heading format:** Show the raw ISO date string (e.g., `2026-04-12`). A future i18n pass can format it; for now keeping it simple and consistent with the existing slot card display.

## Risks / Trade-offs

- **[Risk] Narrow screens** — A three-column layout at 16 rem + flex + 16 rem may be cramped on tablets. → Mitigation: On smaller viewports (`< lg`), collapse back to the existing two-column layout with `lg:grid-cols-[16rem_1fr_16rem] grid-cols-[16rem_1fr]` and hide the sticky continue aside, instead showing the continue button below the grid (existing behaviour). This keeps mobile/tablet experience unchanged.
- **[Risk] Single-day slot lists** — If only one date is returned (e.g., after filtering by date), the grouping adds a single heading above the cards — visually harmless but slightly redundant. No mitigation needed; it is consistent and correct.
