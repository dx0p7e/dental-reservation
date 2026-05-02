## 1. Discount Storage — Migration

- [x] 1.1 Create migration `add_discount_fields_to_appointments_table`: add `discount_pct DECIMAL(5,2) DEFAULT 0.00` and `final_price DECIMAL(8,2) NULL` after `service_id`
- [x] 1.2 Add `discount_pct` and `final_price` to `Appointment::$fillable` and `$casts` (`'discount_pct' => 'decimal:2'`, `'final_price' => 'decimal:2'`)

## 2. Discount Storage — Slot-based Booking

- [x] 2.1 In `AppointmentController::store()`, after resolving the slot: null-guard `$request->user()->loyaltyAccount` — if it exists, join to `LoyaltyTier::where('tier', $account->tier)->first()` to get `discount_bonus_pct`; default `$discountPct = 0` if account or tier row is missing
- [x] 2.2 Compute `$finalPrice = round($service->price * (1 - $discountPct / 100), 2)`; load `$service` from `Service::find($request->service_id)` inside the transaction
- [x] 2.3 Pass `discount_pct` and `final_price` to `Appointment::create()`

## 3. Discount Storage — Request-based Booking

- [x] 3.1 In `AppointmentController::requestStore()`, add `'discount_pct' => 0, 'final_price' => null` to the `Appointment::create()` call

## 4. Discount Storage — API Resource

- [x] 4.1 Add `'discount_pct' => $this->discount_pct` and `'final_price' => $this->final_price` to `AppointmentResource::toArray()`

## 5. NoShow Filament Action

- [x] 5.1 Create `app/Filament/Resources/Appointments/Actions/MarkAppointmentNoShowAction.php` as a standalone Filament `Action` class
- [x] 5.2 Configure the action: label "No-show", icon `heroicon-o-user-minus`, `requiresConfirmation()` with modal heading "Mark as No-show?" and description "Mark this appointment as a no-show? No email will be sent."
- [x] 5.3 Add `->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Confirmed)` and action callback setting `$record->update(['status' => AppointmentStatus::NoShow])`
- [x] 5.4 Register `MarkAppointmentNoShowAction::make()` in `AppointmentsTable::recordActions()` before `EditAction`
- [x] 5.5 In `ConfirmAppointmentRequestAction`'s action callback, inside the existing `DB::transaction`: resolve `$record->patient->loyaltyAccount` (null-guard, default `$discountPct = 0`); load `LoyaltyTier` for the tier; load `Service::find($record->service_id)`; compute `$finalPrice = round($service->price * (1 - $discountPct / 100), 2)`; add `discount_pct` and `final_price` to the `$record->update()` call

## 6. Slot Generation Zero-slot Warning

- [x] 6.1 In `DoctorSchedulesTable`'s `generateSlots` action callback, replace the single `Notification::make()->success()` call with a conditional: if `$result['created'] === 0`, send a `->warning()` notification with title "No slots generated" and body "Check that this schedule has active days configured in the selected range."; otherwise send `->success()` with title "Slots generated" and body "Created {$result['created']} slots, skipped {$result['skipped']} existing."

## 7. Tests

- [x] 7.1 Test slot-based booking stores correct `discount_pct` and `final_price` for a patient with non-zero tier discount
- [x] 7.2 Test slot-based booking with standard tier (0% discount) stores `discount_pct = 0` and `final_price = service.price`
- [x] 7.3 Test request-based booking stores `discount_pct = 0` and `final_price = null`
- [x] 7.4 Test `AppointmentResource` response includes `discount_pct` and `final_price` keys
- [x] 7.5 Test `MarkAppointmentNoShowAction` sets status to `NoShow` for a Confirmed appointment
- [x] 7.6 Test `MarkAppointmentNoShowAction` is not visible for a Pending appointment
- [x] 7.7 Test slot generation action sends warning notification when `created = 0`
- [x] 7.8 Test `ConfirmAppointmentRequestAction` populates `final_price` and `discount_pct` on the appointment record when confirmed

## 8. Code Style

- [x] 8.1 Run `vendor/bin/pint --dirty --format agent`
