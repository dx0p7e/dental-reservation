## ADDED Requirements

### Requirement: Booking store persists draft to localStorage on every mutation
The system SHALL write the fields `selectedDoctor`, `selectedSlot`, and `selectedService` to `localStorage` under the key `booking_draft` whenever any of those three values changes. The stored value SHALL conform to the `BookingDraft` interface: `{ doctor: { id, name, specialization }, slot: { id, date, start_time }, service: { id, name, price, loyalty_discount_pct }, storedAt: ISO 8601 string }`. If `selectedDoctor` is `null` (e.g., after `clearDraft()`), the `booking_draft` key SHALL be removed from `localStorage`.

#### Scenario: Draft is written on slot selection
- **WHEN** a patient selects a slot in `DoctorSlotsView` and `bookingStore.selectedSlot` is set
- **THEN** `localStorage.getItem('booking_draft')` returns a JSON string with `slot.id` matching the selected slot

#### Scenario: Draft is removed when doctor is cleared
- **WHEN** `bookingStore.clearDraft()` is called
- **THEN** `localStorage.getItem('booking_draft')` returns `null`

### Requirement: Booking store rehydrates from localStorage on initialisation
On store initialisation, the system SHALL read `localStorage.getItem('booking_draft')` and parse it as `BookingDraft`. If the draft is valid and not stale, the store SHALL set `selectedDoctor`, `selectedSlot`, and `selectedService` from the draft. A draft is stale if `new Date(\`${draft.slot.date}T${draft.slot.start_time}\`) <= new Date()`. Stale drafts SHALL be discarded and the key removed from `localStorage`.

#### Scenario: Valid draft is restored on app load
- **WHEN** a valid, non-stale `booking_draft` exists in `localStorage` and the store is initialised
- **THEN** `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, and `bookingStore.selectedService` are populated from the draft

#### Scenario: Stale draft is discarded on app load
- **WHEN** a `booking_draft` exists but `slot.date + slot.start_time` is in the past
- **THEN** the store initialises with all three fields as `null` and `localStorage.getItem('booking_draft')` returns `null`

#### Scenario: Missing or malformed draft is ignored
- **WHEN** `localStorage` contains no `booking_draft` key or the value is not valid JSON
- **THEN** the store initialises with all three fields as `null` and no error is thrown

### Requirement: Booking store exposes bookingDraftState computed
The store SHALL expose a `bookingDraftState` computed property returning one of three values:
- `null` — `selectedDoctor` is `null`
- `'slot-selection'` — `selectedDoctor` is set but `selectedSlot` or `selectedService` is `null`
- `'confirmation'` — `selectedDoctor`, `selectedSlot`, and `selectedService` are all set

#### Scenario: State is null with no doctor selected
- **WHEN** `bookingStore.selectedDoctor` is `null`
- **THEN** `bookingStore.bookingDraftState` returns `null`

#### Scenario: State is slot-selection with doctor but no slot
- **WHEN** `bookingStore.selectedDoctor` is set and `bookingStore.selectedSlot` is `null`
- **THEN** `bookingStore.bookingDraftState` returns `'slot-selection'`

#### Scenario: State is confirmation with all three set
- **WHEN** `bookingStore.selectedDoctor`, `bookingStore.selectedSlot`, and `bookingStore.selectedService` are all set
- **THEN** `bookingStore.bookingDraftState` returns `'confirmation'`

### Requirement: Booking store exposes clearDraft() method
The store SHALL expose `clearDraft()`, which resets `selectedDoctor`, `selectedSlot`, and `selectedService` to `null` and removes `booking_draft` from `localStorage`.

#### Scenario: clearDraft resets store and removes localStorage key
- **WHEN** `bookingStore.clearDraft()` is called with a draft in memory and in `localStorage`
- **THEN** all three store fields are `null` and `localStorage.getItem('booking_draft')` returns `null`

### Requirement: TypeScript BookingDraft interface is defined
The system SHALL define a `BookingDraft` interface in `resources/spa/types/index.ts`:
```
{ doctor: { id: number; name: string; specialization: string }, slot: { id: number; date: string; start_time: string }, service: { id: number; name: string; price: string; loyalty_discount_pct: number | null }, storedAt: string }
```

#### Scenario: BookingDraft type is importable
- **WHEN** a component or store imports `BookingDraft` from `@spa/types`
- **THEN** TypeScript compilation succeeds without type errors
