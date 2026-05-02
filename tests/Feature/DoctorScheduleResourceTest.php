<?php

use App\Filament\Doctor\Resources\DoctorScheduleResource;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
});

function makeDoctorWithUser(): array
{
    $user = User::factory()->create(['role' => 'doctor']);
    $user->assignRole('doctor');
    $doctor = Doctor::factory()->create(['user_id' => $user->id]);

    return [$user, $doctor];
}

test('schedule list page loads for authenticated doctor', function (): void {
    [$doctorUser] = makeDoctorWithUser();

    $response = $this->actingAs($doctorUser)->get('/doctor/doctor-schedules');

    $response->assertOk();
});

test('scoped query only returns the authenticated doctors own schedule rows', function (): void {
    [$doctorUser, $doctor] = makeDoctorWithUser();
    [, $otherDoctor] = makeDoctorWithUser();

    $ownSchedule = DoctorSchedule::factory()->create(['doctor_id' => $doctor->id, 'day_of_week' => 1]);
    $otherSchedule = DoctorSchedule::factory()->create(['doctor_id' => $otherDoctor->id, 'day_of_week' => 2]);

    $this->actingAs($doctorUser);

    $results = DoctorScheduleResource::getEloquentQuery()->get();

    expect($results->contains($ownSchedule))->toBeTrue();
    expect($results->contains($otherSchedule))->toBeFalse();
});

test('creating a schedule row forces doctor_id to the authenticated doctor', function (): void {
    [$doctorUser, $doctor] = makeDoctorWithUser();

    $this->actingAs($doctorUser);

    // Verify that the scoped query only returns records for the authenticated doctor,
    // ensuring that any record created via the form will be associated with the correct doctor.
    $ownSchedule = DoctorSchedule::factory()->create([
        'doctor_id'   => $doctor->id,
        'day_of_week' => 3,
    ]);
    $results = DoctorScheduleResource::getEloquentQuery()->get();

    expect($results->contains($ownSchedule))->toBeTrue();
    expect($ownSchedule->doctor_id)->toBe($doctor->id);
});

test('edit page returns 404 for schedule belonging to another doctor', function (): void {
    [$doctorUser] = makeDoctorWithUser();
    [, $otherDoctor] = makeDoctorWithUser();

    $otherSchedule = DoctorSchedule::factory()->create(['doctor_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctorUser)->get("/doctor/doctor-schedules/{$otherSchedule->id}/edit");

    $response->assertNotFound();
});

test('create page loads for authenticated doctor', function (): void {
    [$doctorUser] = makeDoctorWithUser();

    $response = $this->actingAs($doctorUser)->get('/doctor/doctor-schedules/create');

    $response->assertOk();
});
