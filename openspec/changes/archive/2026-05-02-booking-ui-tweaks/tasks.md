## 1. Three-Column Sticky Layout

- [x] 1.1 Replace the `flex gap-8` wrapper in `DoctorSlotsView.vue` with a CSS grid using `grid lg:grid-cols-[16rem_1fr_16rem] grid-cols-[16rem_1fr] gap-8`
- [x] 1.2 Add `sticky top-6 self-start` to the left `<aside>` (service select + date filter)
- [x] 1.3 Extract the Continue button and hint text from the bottom of the centre column into a new right `<aside>` with `sticky top-6 self-start hidden lg:block`
- [x] 1.4 Keep the Continue button + hint text below the grid for small screens (inside the centre column, visible only when `lg:hidden`)

## 2. Day-Grouped Slot Rendering

- [x] 2.1 Add a `groupedSlots` computed property that returns a `Map<string, Slot[]>` keyed by `slot.date`, built from `slots.value`
- [x] 2.2 Replace the flat `v-for="slot in slots"` grid with a `v-for="[date, daySlots] in groupedSlots"` loop that renders a date heading (`<h3>`) followed by a sub-grid of slot cards for each day
- [x] 2.3 Style the date heading (e.g., `text-sm font-semibold text-clinic-text mb-2 mt-4 first:mt-0`) to visually separate the groups

## 3. Tests

- [x] 3.1 Add a Pest feature test (or update existing booking UI tests) asserting that `DoctorSlotsView` renders slot cards grouped under date headings when multiple dates are present
- [x] 3.2 Run `php artisan test --compact --filter=DoctorSlots` to confirm all tests pass

## 4. Code Quality

- [x] 4.1 Run `vendor/bin/pint --dirty --format agent` and fix any style issues
