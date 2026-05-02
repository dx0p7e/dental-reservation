<?php

use App\Filament\Resources\DoctorSchedules\Pages\CreateDoctorSchedule;
use App\Filament\Resources\DoctorSchedules\Pages\EditDoctorSchedule;
use App\Filament\Resources\DoctorSchedules\Pages\ListDoctorSchedules;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
});

test('list page renders', function (): void {
    Livewire::test(ListDoctorSchedules::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateDoctorSchedule::class)
        ->assertSuccessful();
});

test('can create a schedule slot', function (): void {
    $doctor = Doctor::factory()->create();

    Livewire::test(CreateDoctorSchedule::class)
        ->fillForm([
            'doctor_id'             => $doctor->id,
            'day_of_week'           => 1,
            'start_time'            => '09:00',
            'end_time'              => '17:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DoctorSchedule::query()->where('doctor_id', $doctor->id)->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $schedule = DoctorSchedule::factory()->create();

    Livewire::test(EditDoctorSchedule::class, ['record' => $schedule->id])
        ->assertSuccessful();
});

test('can edit a schedule slot', function (): void {
    $schedule = DoctorSchedule::factory()->create();

    Livewire::test(EditDoctorSchedule::class, ['record' => $schedule->id])
        ->fillForm(['slot_duration_minutes' => 45])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($schedule->fresh()->slot_duration_minutes)->toBe(45);
});

test('overlapping slot on create is blocked', function (): void {
    $doctor = Doctor::factory()->create();

    DoctorSchedule::factory()->create([
        'doctor_id'   => $doctor->id,
        'day_of_week' => 2,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
    ]);

    Livewire::test(CreateDoctorSchedule::class)
        ->fillForm([
            'doctor_id'             => $doctor->id,
            'day_of_week'           => 2,
            'start_time'            => '11:00',
            'end_time'              => '14:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ])
        ->call('create');

    expect(DoctorSchedule::query()->where('doctor_id', $doctor->id)->count())->toBe(1);
});

test('non-overlapping slots on different days are allowed', function (): void {
    $doctor = Doctor::factory()->create();

    DoctorSchedule::factory()->create([
        'doctor_id'   => $doctor->id,
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
    ]);

    Livewire::test(CreateDoctorSchedule::class)
        ->fillForm([
            'doctor_id'             => $doctor->id,
            'day_of_week'           => 2,
            'start_time'            => '09:00',
            'end_time'              => '17:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DoctorSchedule::query()->where('doctor_id', $doctor->id)->count())->toBe(2);
});
