## Why

Three gaps remain after FR completion that would be noticeable in a live demo or thesis evaluation: patients receive no confirmed final price when booking, admins have no action for patients who simply don't attend a confirmed appointment, and the slot generation UI silently reports success even when it produces no slots (misleading operators who misconfigure schedules).

## What Changes

- **Discount storage on booking**: Migration adds `discount_pct DECIMAL(5,2) DEFAULT 0.00` and `final_price DECIMAL(8,2) NULL` to `appointments`. The slot-based booking flow (`POST /api/v1/appointments`) resolves the patient's loyalty tier at booking time and stores `discount_pct` and `final_price`. The request-based booking flow stores `discount_pct = 0` and `final_price = null` (price is applied when admin confirms and assigns a slot/service). `AppointmentResource` exposes both fields.
- **NoShow Filament action**: A single inline action on `AppointmentsTable` visible only when `status === Confirmed`. Requires a confirmation modal. Sets `status = AppointmentStatus::NoShow`. No email is sent.
- **Slot generation zero-slot warning**: `DoctorSchedulesTable`'s existing `generateSlots` action already fires a notification — but always as success, even when `created === 0`. Change the action to show a **warning** notification when `created === 0` and a success notification when `created > 0`.

## Capabilities

### New Capabilities

- `appointment-discount-storage`: Discount percentage and final price are captured at booking time and exposed via the API
- `appointment-no-show`: Admin can mark a confirmed appointment as no-show
- `slot-generation-zero-feedback`: Slot generation action warns when no new slots were created

### Modified Capabilities

<!-- None — no existing spec-level behavior changes -->

## Impact

- New migration for `appointments` table (additive — no existing data changed)
- `AppointmentController::store()` — reads `LoyaltyAccount` + `LoyaltyTier` to compute and store discount
- `AppointmentController::requestStore()` — stores `discount_pct = 0`, `final_price = null`
- `AppointmentResource` — exposes `discount_pct`, `final_price`
- `app/Filament/Resources/Appointments/Tables/AppointmentsTable.php` — NoShow action added
- `app/Filament/Resources/DoctorSchedules/Tables/DoctorSchedulesTable.php` — notification logic split by `created === 0`
- No new dependencies
