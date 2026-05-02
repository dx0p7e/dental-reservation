## Context

### Appointment booking flows

Two flows exist post-FR14:
- **Slot-based** (`POST /api/v1/appointments`): patient picks a slot; `doctor_id` and `service_id` are known at booking time — discount can be computed immediately.
- **Request-based** (`POST /api/v1/appointments/request`): patient picks a service and a preferred date, no slot assigned; admin confirms and assigns the slot later — service price is known but final pricing should be deferred to confirmation.

### Discount resolution chain

```
patient → LoyaltyAccount.tier (string) → LoyaltyTier.tier (enum) → LoyaltyTier.discount_bonus_pct
```

`LoyaltyTier.discount_bonus_pct` is `DECIMAL(5,2)`. Standard tier exists with a threshold of 0 points. `LoyaltyAccount` is guaranteed to exist for any patient (auto-created by `UserObserver`).

### SlotGenerationService return contract

`generateForSchedule()` already returns `['created' => int, 'skipped' => int]`. The existing action reads this and always fires `Notification::make()->success()`. No service change needed — only the Filament action notification branch.

### AppointmentsTable existing actions

`ConfirmAppointmentRequestAction` (custom action, file in `app/Filament/Resources/Appointments/Actions/`) and `EditAction`. The NoShow action should follow the same file pattern.

## Goals / Non-Goals

**Goals:**

- Persist discount and final price at booking for slot-based appointments
- Expose discount/final price in the API response
- Give admins a one-click no-show action on confirmed appointments
- Show a warning (not success) when slot generation creates zero new slots

**Non-Goals:**

- Retroactive discount computation for existing appointments
- Loyalty point deduction/reversal on no-show
- Email notification for no-show
- Free-form discount editing by admins
- Bulk no-show action

## Decisions

**Decision 1 — Resolve discount at booking time from LoyaltyTier, not LoyaltyAccount**

`LoyaltyAccount.tier` is a string (`'standard'`, `'silver'`, `'gold'`). The canonical discount rate lives in `LoyaltyTier.discount_bonus_pct` (joined via `tier` column). The controller should do:

```php
$tier   = LoyaltyTier::where('tier', $loyaltyAccount->tier)->first();
$discountPct = $tier?->discount_bonus_pct ?? 0;
$finalPrice  = round($service->price * (1 - $discountPct / 100), 2);
```

*Alternative considered*: Cache the discount rate on `LoyaltyAccount`. Rejected — `LoyaltyTier` is the source of truth; a two-step join is trivial.

---

**Decision 2 — `final_price` is NULL for request-based bookings**

For request-based bookings no slot or confirmed service price exists yet. Storing `null` is semantically correct and avoids displaying a stale price before admin confirmation.

*Alternative considered*: Compute `final_price` using `service_id` at request time. Rejected — the service price may change between request and confirmation; consistency requires computing at the time the booking is finalised.

---

**Decision 3 — NoShow action extracted to its own class**

Following the existing pattern (`ConfirmAppointmentRequestAction` lives in `app/Filament/Resources/Appointments/Actions/`), the NoShow action will be a standalone class `MarkAppointmentNoShowAction` in the same directory. It is registered in `AppointmentsTable::recordActions()`.

---

**Decision 4 — NoShow visibility guard uses `$record->status === AppointmentStatus::Confirmed`**

The action is only visible and enabled when the current appointment status is `Confirmed`. A `->visible()` closure handles this.

---

**Decision 5 — Slot generation uses warning vs success notification split**

When `$result['created'] === 0`: fire `Notification::make()->warning()` with title "No slots generated" and body "Check that this schedule has active days configured in the selected range."  
When `$result['created'] > 0`: keep existing success notification (updated body to mention `skipped` count too for clarity).

## Risks / Trade-offs

- [Null `LoyaltyAccount`] → `LoyaltyAccount` is guaranteed by `UserObserver` for all patient-role users. The API is auth-gated to patients only. Still use `??` default of `0` as a defensive fallback.
- [Concurrent discount rate change] → A tier rate change between booking and payment is an accepted business risk at thesis scope; we record `discount_pct` at booking time so the stored value is always the one applied.
- [AppointmentObserver fires mail on created] → The observer sends `AppointmentConfirmed` mail on `Appointment::create()`. The discount fields are stored columns and don't affect this; no observer change needed.
