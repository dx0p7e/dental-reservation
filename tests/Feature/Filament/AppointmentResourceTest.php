<?php

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Doctors\Pages\EditDoctor;
use App\Filament\Resources\Doctors\RelationManagers\PatientsRelationManager;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
    Mail::fake();
});

test('list page renders', function (): void {
    Livewire::test(ListAppointments::class)
        ->assertSuccessful();
});

test('create page renders', function (): void {
    Livewire::test(CreateAppointment::class)
        ->assertSuccessful();
});

test('can create an appointment', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id]);

    Livewire::test(CreateAppointment::class)
        ->fillForm([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'slot_id' => $slot->id,
            'status' => AppointmentStatus::Pending->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Appointment::query()->where('patient_id', $patient->id)->exists())->toBeTrue();
});

test('edit page renders', function (): void {
    $appointment = Appointment::factory()->create();

    Livewire::test(EditAppointment::class, ['record' => $appointment->id])
        ->assertSuccessful();
});

test('can edit an appointment', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

    Livewire::test(EditAppointment::class, ['record' => $appointment->id])
        ->fillForm([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'service_id' => $appointment->service_id,
            'slot_id' => $appointment->slot_id,
            'status' => AppointmentStatus::Confirmed->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});

test('patients relation manager renders', function (): void {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id]);

    Livewire::test(PatientsRelationManager::class, [
        'ownerRecord' => $doctor,
        'pageClass' => EditDoctor::class,
    ])->assertSuccessful();
});
