<?php

use App\Models\DoctorSchedule;
use App\Models\ScheduleSlot;
use App\Services\SlotGenerationService;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->service = app(SlotGenerationService::class);
});

test('generates slots for an active schedule in the date range', function (): void {
    // Monday = isoWeekday 1
    $monday = Carbon::parse('next monday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $result = $this->service->generateForSchedule($schedule, $monday, 7);

    expect($result['created'])->toBe(2)
        ->and($result['skipped'])->toBe(0);

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)
        ->where('date', $monday->toDateString())
        ->count())->toBe(2);
});

test('service is idempotent - running twice produces no duplicates', function (): void {
    $monday = Carbon::parse('next monday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $this->service->generateForSchedule($schedule, $monday, 7);
    $result = $this->service->generateForSchedule($schedule, $monday, 7);

    expect($result['created'])->toBe(0)
        ->and($result['skipped'])->toBe(2);

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)
        ->where('date', $monday->toDateString())
        ->count())->toBe(2);
});

test('slot times match slot_duration_minutes', function (): void {
    $monday = Carbon::parse('next monday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $result = $this->service->generateForSchedule($schedule, $monday, 1);

    expect($result['created'])->toBe(6);

    $slots = ScheduleSlot::where('doctor_id', $schedule->doctor_id)
        ->where('date', $monday->toDateString())
        ->orderBy('start_time')
        ->get();

    expect($slots->first()->start_time)->toBe('09:00:00')
        ->and($slots->first()->end_time)->toBe('09:30:00')
        ->and($slots->last()->start_time)->toBe('11:30:00')
        ->and($slots->last()->end_time)->toBe('12:00:00');
});

test('dates not matching day_of_week produce no slots', function (): void {
    // day_of_week=1 (Monday), start from a Tuesday range of 7 days with only one Monday
    $tuesday = Carbon::parse('next tuesday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => 3, // Wednesday
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    $result = $this->service->generateForSchedule($schedule, $tuesday, 1);

    expect($result['created'])->toBe(0);
});
