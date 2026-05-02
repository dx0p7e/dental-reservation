<?php

use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Support\Facades\Schema;

it('doctor_service table exists with expected columns', function (): void {
    expect(Schema::hasTable('doctor_service'))->toBeTrue();
    expect(Schema::hasColumns('doctor_service', ['doctor_id', 'service_id']))->toBeTrue();
});

it('GET /api/v1/doctors includes services array per doctor', function (): void {
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $doctor->services()->attach($service->id);

    $response = $this->getJson('/api/v1/doctors');

    $response->assertOk();

    $doctorData = collect($response->json('data') ?? $response->json())
        ->firstWhere('id', $doctor->id);

    expect($doctorData)->not->toBeNull();
    expect($doctorData['services'])->toBeArray();
    expect($doctorData['services'])->toHaveCount(1);
    expect($doctorData['services'][0]['id'])->toBe($service->id);
});

it('GET /api/v1/doctors includes empty services array for doctor with no services', function (): void {
    $doctor = Doctor::factory()->create();

    $response = $this->getJson('/api/v1/doctors');

    $response->assertOk();

    $doctorData = collect($response->json('data') ?? $response->json())
        ->firstWhere('id', $doctor->id);

    expect($doctorData['services'])->toBeArray()->toBeEmpty();
});

it('GET /api/v1/doctors/{doctor}/services returns only that doctors services', function (): void {
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $otherService = Service::factory()->create();
    $doctor->services()->attach($service->id);

    $response = $this->getJson("/api/v1/doctors/{$doctor->id}/services");

    $response->assertOk();
    $ids = collect($response->json('data') ?? $response->json())->pluck('id')->all();
    expect($ids)->toBe([$service->id]);
    expect($ids)->not->toContain($otherService->id);
});

it('GET /api/v1/doctors/{doctor}/services returns empty array for doctor with no services', function (): void {
    $doctor = Doctor::factory()->create();

    $response = $this->getJson("/api/v1/doctors/{$doctor->id}/services");

    $response->assertOk();
    $items = $response->json('data') ?? $response->json();
    expect($items)->toBeArray()->toBeEmpty();
});

it('GET /api/v1/doctors/9999/services returns 404', function (): void {
    $this->getJson('/api/v1/doctors/9999/services')->assertNotFound();
});
