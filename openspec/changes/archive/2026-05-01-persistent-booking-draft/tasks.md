## 1. TypeScript Types

- [x] 1.1 Add `BookingDraft` interface to `resources/spa/types/index.ts` with shape: `{ doctor: { id: number; name: string; specialization: string }; slot: { id: number; date: string; start_time: string } | null; service: { id: number; name: string; price: string; loyalty_discount_pct: number | null } | null; storedAt: string }`. Note: `slot` and `service` are nullable because the watch fires whenever any of the three refs changes — including the moment only `selectedDoctor` is set but slot/service are still null.

## 2. Booking Store — Persistence & Draft Logic

- [x] 2.1 In `resources/spa/stores/booking.ts`, import `watch` and `computed` from Vue and `BookingDraft` from `@spa/types`
- [x] 2.2 Add a `loadDraft()` helper that reads `localStorage.getItem('booking_draft')`, parses it as `BookingDraft`, checks staleness (`new Date(\`${draft.slot.date}T${draft.slot.start_time}\`) <= new Date()`), and returns `null` for stale or malformed drafts
- [x] 2.3 Rehydrate the three refs on store initialisation by calling `loadDraft()` and setting `selectedDoctor`, `selectedSlot`, `selectedService` from the result (or leaving them `null`)
- [x] 2.4 Add a `watch([selectedDoctor, selectedSlot, selectedService], () => { ... }, { deep: true })` with the following exact serialiser — explicitly pick slim fields to avoid storing the full `Doctor` object (which now includes `services[]`):
  ```ts
  if (!selectedDoctor.value) {
    localStorage.removeItem('booking_draft')
    return
  }
  const draft: BookingDraft = {
    doctor: { id: selectedDoctor.value.id, name: selectedDoctor.value.name, specialization: selectedDoctor.value.specialization },
    slot: selectedSlot.value
      ? { id: selectedSlot.value.id, date: selectedSlot.value.date, start_time: selectedSlot.value.start_time }
      : null,
    service: selectedService.value
      ? { id: selectedService.value.id, name: selectedService.value.name, price: selectedService.value.price, loyalty_discount_pct: selectedService.value.loyalty_discount_pct }
      : null,
    storedAt: new Date().toISOString(),
  }
  localStorage.setItem('booking_draft', JSON.stringify(draft))
  ```
- [x] 2.5 Add `bookingDraftState` computed: returns `null` when `selectedDoctor` is null; `'slot-selection'` when doctor is set but slot or service is null; `'confirmation'` when all three are set
- [x] 2.6 Add `clearDraft()` function that sets all three refs to `null` and calls `localStorage.removeItem('booking_draft')`
- [x] 2.7 Export `bookingDraftState` and `clearDraft` from the store's return object

## 3. AppNavbar — Continue Booking Button

- [x] 3.1 In `resources/spa/components/AppNavbar.vue`, import `useBookingStore` and `computed` alongside existing imports
- [x] 3.2 Instantiate `bookingStore = useBookingStore()` and create `continueBookingRoute = computed(() => bookingStore.bookingDraftState === 'confirmation' ? '/dashboard/book' : \`/doctors/${bookingStore.selectedDoctor?.id}/slots\`)`
- [x] 3.3 In the navbar template, add a `<RouterLink v-if="authStore.isAuthenticated && bookingStore.bookingDraftState !== null" :to="continueBookingRoute" class="border border-clinic-teal text-clinic-teal rounded-full px-4 py-1.5 text-sm hover:bg-teal-50">Continue booking</RouterLink>` between the public nav links and the user dropdown

## 4. DoctorSlotsView — Draft Restore on Mount

- [x] 4.1 In the `onMounted` handler of `resources/spa/views/DoctorSlotsView.vue`, after the existing doctor fetch, add draft restore logic: if `bookingStore.selectedDoctor?.id === +doctorId` AND `bookingStore.selectedSlot` is set, pre-select the stored slot (set `selectedSlotId.value = bookingStore.selectedSlot.id`) and the stored service (`selectedServiceId.value = bookingStore.selectedService?.id ?? null`)
- [x] 4.2 If the loaded route's `doctorId` does NOT match `bookingStore.selectedDoctor?.id`, call `bookingStore.clearDraft()`

## 5. BookView — Clear Draft on Confirm

- [x] 5.1 In `resources/spa/views/BookView.vue` `bookNow()`, replace the `bookingStore.reset()` call with `bookingStore.clearDraft()`

## 6. Tests

- [x] 6.1 Install Vitest dev dependencies: `pnpm add -D vitest @pinia/testing @vue/test-utils jsdom` and add a `vitest` config block to `vite.config.ts` (`test: { environment: 'jsdom' }`)
- [x] 6.2 Create `resources/spa/stores/__tests__/booking.spec.ts` using `createTestingPinia` and a `localStorage` mock via `vi.stubGlobal('localStorage', localStorageMock)`. The spec MUST cover all three scenarios as cases in one file:
  - Stale draft is discarded: set `booking_draft` with a slot datetime in the past → `loadDraft()` returns `null` and key is removed
  - `bookingDraftState` returns `null` / `'slot-selection'` / `'confirmation'` for the three store states
  - `clearDraft()` sets all three refs to `null` and removes the `booking_draft` key
- [x] 6.3 Run `php artisan test --compact` to confirm all existing backend tests still pass after frontend-only changes

## 7. Code Quality

- [x] 7.1 Run `vendor/bin/pint --dirty --format agent` to format any changed PHP files
- [x] 7.2 Run `php artisan test --compact` and confirm all tests pass
