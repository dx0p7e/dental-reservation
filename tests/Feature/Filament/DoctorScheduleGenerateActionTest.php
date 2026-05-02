<?php

use App\Filament\Resources\DoctorSchedules\Pages\ListDoctorSchedules;
use App\Models\DoctorSchedule;
use App\Models\ScheduleSlot;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
});

test('generate slots action creates slots for the schedule', function (): void {
    $monday = Carbon::parse('next monday');

    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => $monday->isoWeekday(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ]);

    Livewire::test(ListDoctorSchedules::class)
        ->callAction(
            TestAction::make('generateSlots')->table($schedule),
            data: ['days' => 7],
        )
        ->assertHasNoErrors();

    expect(ScheduleSlot::where('doctor_id', $schedule->doctor_id)->count())->toBeGreaterThan(1);
});
