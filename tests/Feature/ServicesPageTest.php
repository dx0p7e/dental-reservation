<?php

use App\Models\Service;

test('GET /api/v1/services returns 200 with correct structure for a seeded service', function (): void {
    $service = Service::factory()->create([
        'name'             => 'Teeth Cleaning',
        'description'      => 'Professional cleaning.',
        'duration_minutes' => 45,
        'price'            => '60.00',
    ]);

    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonFragment([
            'id'               => $service->id,
            'name'             => 'Teeth Cleaning',
            'description'      => 'Professional cleaning.',
            'duration_minutes' => 45,
            'price'            => '60.00',
        ]);
});

test('GET /api/v1/services is publicly accessible without authentication', function (): void {
    $this->getJson('/api/v1/services')
        ->assertOk();
});

test('GET /api/v1/services returns an empty data array when no services exist', function (): void {
    Service::query()->delete();

    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJson(['data' => []]);
});
