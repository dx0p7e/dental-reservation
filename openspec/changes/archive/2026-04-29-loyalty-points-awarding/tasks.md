# Tasks: Loyalty Points Awarding

## 1. AppointmentObserver

- [x] 1.1 Run `php artisan make:observer AppointmentObserver --model=Appointment --no-interaction`
- [x] 1.2 Implement `updated(Appointment $appointment): void` — guard: `wasChanged('status') && status === AppointmentStatus::Completed`
- [x] 1.3 Inside the guard, look up `LoyaltyRule::where('service_id', $appointment->service_id)->first()`; return early if null
- [x] 1.4 Wrap the following in `DB::transaction`: `firstOrCreate` the `LoyaltyAccount` for `patient_id`; compute `$newBalance = $account->points_balance + $rule->points_earned` before writing; create `LoyaltyTransaction` (type `earn`, `points_delta`, `appointment_id`, `loyalty_account_id`); call `increment('points_balance', $rule->points_earned)` on the account; recalculate tier inside the same transaction: query `LoyaltyTier::where('points_threshold', '<=', $newBalance)->orderByDesc('points_threshold')->value('tier')`; if result is non-null and differs from current tier, update `LoyaltyAccount.tier`

## 2. Observer Registration

- [x] 2.1 In `AppServiceProvider::boot()`, add `Appointment::observe(AppointmentObserver::class)` alongside the existing `User::observe(UserObserver::class)`

## 3. Tests

- [x] 3.1 Run `php artisan make:test --pest AppointmentObserverTest --no-interaction`
- [x] 3.2 Test: completing an appointment with a matching rule creates a `LoyaltyTransaction` and increments `points_balance`
- [x] 3.3 Test: completing an appointment with no matching rule does NOT create a `LoyaltyTransaction`
- [x] 3.4 Test: re-saving an already-completed appointment does NOT create a duplicate `LoyaltyTransaction`
- [x] 3.5 Test: changing status to non-Completed (e.g. `Confirmed`) does NOT create a `LoyaltyTransaction`
- [x] 3.6 Test: `LoyaltyAccount` is created via `firstOrCreate` if it does not exist when points are awarded
- [x] 3.7 Test: tier is upgraded to `silver` when balance crosses the silver threshold after earning
- [x] 3.8 Test: tier remains unchanged when balance does not cross the next threshold

## 4. Code Style

- [x] 4.1 Run `vendor/bin/pint app/Observers/AppointmentObserver.php app/Providers/AppServiceProvider.php --format agent`
