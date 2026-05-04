<?php

use App\Models\DoctorSchedule;
use App\Models\ScheduleSlot;
use Carbon\Carbon;

test('command generates slots for active schedules', function (): void {
    $monday = Carbon::parse('next monday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $this->artisan('slots:generate', [
        '--date' => $monday->toDateString(),
        '--days' => 1,
    ])->assertSuccessful();

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)
        ->where('date', $monday->toDateString())
        ->count())->toBe(2);
});

test('command skips inactive schedules', function (): void {
    $monday = Carbon::parse('next monday');

    DoctorSchedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => false,
    ]);

    $this->artisan('slots:generate', [
        '--date' => $monday->toDateString(),
        '--days' => 7,
    ])->assertSuccessful();

    expect(ScheduleSlot::count())->toBe(0);
});

test('command respects --date and --days options', function (): void {
    // Wednesday = isoWeekday 3
    $wednesday = Carbon::parse('next wednesday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 3,
        'start_time' => '10:00',
        'end_time' => '11:00',
        'slot_duration_minutes' => 60,
        'is_active' => true,
    ]);

    // Generate only 3 days starting from Wednesday — should hit exactly 1 Wednesday
    $this->artisan('slots:generate', [
        '--date' => $wednesday->toDateString(),
        '--days' => 3,
    ])->assertSuccessful();

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)->count())->toBe(1);

    // Extend by 7 more days — should add 1 more Wednesday
    $this->artisan('slots:generate', [
        '--date' => $wednesday->toDateString(),
        '--days' => 10,
    ])->assertSuccessful();

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)->count())->toBe(2);
});
