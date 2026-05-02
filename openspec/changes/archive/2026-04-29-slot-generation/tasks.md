# Tasks: Slot Generation

## 1. Service Class

- [x] 1.1 Run `php artisan make:class Services/SlotGenerationService --no-interaction`
- [x] 1.2 Implement `generateForSchedule(DoctorSchedule $schedule, Carbon $from, int $days): array`: iterate `$days` dates starting from `$from`; for each date use `$carbon->isoWeekday()` (1=Mon–7=Sun) to match `$schedule->day_of_week`; if matched, loop start→end in `slot_duration_minutes` increments calling `ScheduleSlot::firstOrCreate([doctor_id, date, start_time], [end_time, slot_type='self', is_booked=false])`; return `['created' => int, 'skipped' => int]`

## 2. Artisan Command

- [x] 2.1 Run `php artisan make:command GenerateSlotsCommand --no-interaction`
- [x] 2.2 Set command signature to `slots:generate {--date= : Start date (YYYY-MM-DD), defaults to today} {--days=14 : Number of days ahead to generate}`
- [x] 2.3 Inject `SlotGenerationService` via constructor; in `handle()`: resolve start date, query all `DoctorSchedule::where('is_active', true)->get()`, call `$service->generateForSchedule($schedule, $from, $days)` for each, accumulate totals
- [x] 2.4 Output a summary line: `"Generated {$created} slots, skipped {$skipped} existing."`

## 3. Daily Schedule

- [x] 3.1 In `routes/console.php`, register `Schedule::command('slots:generate')->daily()` to run once per day

## 4. Filament Action

- [x] 4.1 In `DoctorSchedulesTable`, add a `Tables\Actions\Action::make('generateSlots')` row action labelled "Generate Slots" with icon `heroicon-o-calendar-days`
- [x] 4.2 Add a modal form field `TextInput::make('days')->label('Days ahead')->numeric()->default(14)->minValue(1)->maxValue(365)->required()`
- [x] 4.3 In the action's `action()` closure: inject/resolve `SlotGenerationService`, call `$service->generateForSchedule($record, now(), (int) $data['days'])`, show a success `Notification` with the returned created/skipped counts

## 5. Tests

- [x] 5.1 Run `php artisan make:test --pest SlotGenerationServiceTest --no-interaction`
- [x] 5.2 Test: active schedule → correct slots created for date range
- [x] 5.3 Test: service is idempotent (running twice produces no duplicates)
- [x] 5.4 Test: slot times match `slot_duration_minutes` (count and boundary times)
- [x] 5.5 Test: dates outside the schedule's `day_of_week` produce no slots
- [x] 5.6 Run `php artisan make:test --pest GenerateSlotsCommandTest --no-interaction`
- [x] 5.7 Test: command skips inactive schedules; test `--date` and `--days` options
- [x] 5.8 Run `php artisan make:test --pest Filament/DoctorScheduleGenerateActionTest --no-interaction`
- [x] 5.9 Test: Filament "Generate Slots" action creates slots for the selected schedule

## 6. Code Style

- [x] 6.1 Run `vendor/bin/pint app/Services/SlotGenerationService.php app/Console/Commands/GenerateSlotsCommand.php app/Filament/Resources/DoctorSchedules/ routes/console.php --format agent`
