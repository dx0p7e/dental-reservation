<?php

use App\Models\Doctor;
use App\Models\ScheduleSlot;

test('doctor list returns only active doctors', function (): void {
    Doctor::factory()->create(['is_active' => true]);
    Doctor::factory()->create(['is_active' => false]);

    $this->getJson('/api/v1/doctors')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('doctor resource returns null photo_url when photo_path is null', function (): void {
    $doctor = Doctor::factory()->create(['is_active' => true, 'photo_path' => null]);

    $this->getJson("/api/v1/doctors/{$doctor->id}")
        ->assertOk()
        ->assertJsonPath('data.photo_url', null);
});

test('doctor resource returns absolute photo_url when photo_path is set', function (): void {
    $doctor = Doctor::factory()->create(['is_active' => true, 'photo_path' => 'doctors/test.jpg']);

    $response = $this->getJson("/api/v1/doctors/{$doctor->id}")
        ->assertOk();

    expect($response->json('data.photo_url'))->toBeString()->toContain('doctors/test.jpg');
});

test('slots endpoint returns only non-booked slots for a doctor', function (): void {
    $doctor = Doctor::factory()->create(['is_active' => true]);
    ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);
    ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => true]);

    $this->getJson("/api/v1/doctors/{$doctor->id}/slots")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('slots endpoint supports date filter', function (): void {
    $doctor = Doctor::factory()->create(['is_active' => true]);
    ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false, 'date' => '2026-06-01']);
    ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false, 'date' => '2026-06-02']);

    $this->getJson("/api/v1/doctors/{$doctor->id}/slots?date=2026-06-01")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
