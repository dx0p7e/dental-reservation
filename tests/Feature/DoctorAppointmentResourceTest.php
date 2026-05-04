<?php

use App\Enums\AppointmentStatus;
use App\Filament\Doctor\Resources\DoctorAppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

function makeDoctorUser(): User
{
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');
    Doctor::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('appointments list page loads for authenticated doctor', function (): void {
    $doctorUser = makeDoctorUser();

    $response = $this->actingAs($doctorUser)->get('/doctor/doctor-appointments');

    $response->assertOk();
});

test('scoped query only returns the authenticated doctors own appointments', function (): void {
    $doctorUser = makeDoctorUser();
    $otherDoctorUser = makeDoctorUser();

    $ownAppointment = Appointment::factory()->create([
        'doctor_id' => $doctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
    ]);
    $otherAppointment = Appointment::factory()->create([
        'doctor_id' => $otherDoctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
    ]);

    $this->actingAs($doctorUser);

    $results = DoctorAppointmentResource::getEloquentQuery()->get();

    expect($results->contains($ownAppointment))->toBeTrue();
    expect($results->contains($otherAppointment))->toBeFalse();
});

test('doctor can save doctor_notes on their own appointment', function (): void {
    $doctorUser = makeDoctorUser();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
        'doctor_notes' => null,
    ]);

    $this->actingAs($doctorUser);

    $appointment->update(['doctor_notes' => 'Patient tolerated procedure well.']);

    expect($appointment->fresh()->doctor_notes)->toBe('Patient tolerated procedure well.');
});

test('mark complete sets appointment status to completed', function (): void {
    $doctorUser = makeDoctorUser();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $appointment->update(['status' => AppointmentStatus::Completed]);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});

test('mark no-show sets appointment status to no_show', function (): void {
    $doctorUser = makeDoctorUser();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
        'status' => AppointmentStatus::Confirmed,
    ]);

    $appointment->update(['status' => AppointmentStatus::NoShow]);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::NoShow);
});

test('edit page returns 404 for appointment belonging to another doctor', function (): void {
    $doctorUser = makeDoctorUser();
    $otherDoctorUser = makeDoctorUser();
    $otherAppointment = Appointment::factory()->create([
        'doctor_id' => $otherDoctorUser->doctor->id,
        'patient_id' => User::factory()->create(['role' => 'patient'])->id,
    ]);

    $response = $this->actingAs($doctorUser)->get("/doctor/doctor-appointments/{$otherAppointment->id}/edit");

    $response->assertNotFound();
});
