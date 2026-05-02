<?php

use App\Models\PatientReview;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

// GET /api/v1/reviews

test('GET reviews returns 200 with data array', function (): void {
    $patient = User::factory()->create(['name' => 'Jonas Simonaitis']);
    PatientReview::factory()->create([
        'patient_id'   => $patient->id,
        'rating'       => 5,
        'title'        => 'Puiku',
        'body'         => 'Labai gera klinika ir malonus personalas.',
        'is_published' => true,
    ]);

    $this->getJson('/api/v1/reviews')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'rating', 'title', 'body', 'patient_name', 'created_at']]]);
});

test('GET reviews anonymises patient name to first name and last initial', function (): void {
    $patient = User::factory()->create(['name' => 'Jonas Simonaitis']);
    PatientReview::factory()->create([
        'patient_id'   => $patient->id,
        'rating'       => 5,
        'body'         => 'Gera klinika, rekomenduoju visiems draugams.',
        'is_published' => true,
    ]);

    $response = $this->getJson('/api/v1/reviews')->assertOk();

    $patientName = $response->json('data.0.patient_name');
    expect($patientName)->toBe('Jonas S.');
});

test('GET reviews excludes unpublished reviews', function (): void {
    $published   = User::factory()->create(['name' => 'Egle Mikalauskaite']);
    $unpublished = User::factory()->create(['name' => 'Ruta Jankunaite']);

    PatientReview::factory()->create([
        'patient_id'   => $published->id,
        'rating'       => 5,
        'body'         => 'Labai gera klinika ir malonus personalas.',
        'is_published' => true,
    ]);
    PatientReview::factory()->create([
        'patient_id'   => $unpublished->id,
        'rating'       => 4,
        'body'         => 'Patenkinta paslauga, rekomenduosiu draugams.',
        'is_published' => false,
    ]);

    $response = $this->getJson('/api/v1/reviews')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.patient_name'))->toBe('Egle M.');
});

// POST /api/v1/reviews

test('POST reviews creates a review and returns 201', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/reviews', [
        'rating' => 5,
        'title'  => 'Puiku!',
        'body'   => 'Labai patenkinta apsilankymu, rekomenduosiu draugams.',
    ])->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'rating', 'title', 'body', 'patient_name', 'created_at']]);

    expect(PatientReview::where('patient_id', $patient->id)->exists())->toBeTrue();
});

test('POST reviews returns 409 when patient already reviewed', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');
    Sanctum::actingAs($patient);

    PatientReview::factory()->create([
        'patient_id' => $patient->id,
        'body'       => 'Labai gera klinika ir malonus personalas.',
    ]);

    $this->postJson('/api/v1/reviews', [
        'rating' => 4,
        'body'   => 'Antrasis bandymas palikti atsiliepimą.',
    ])->assertStatus(409);
});

test('POST reviews returns 422 on invalid rating', function (): void {
    $patient = User::factory()->create();
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/reviews', [
        'rating' => 6,
        'body'   => 'Kažkoks tekstas atsiliepimui apie kliniką.',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['rating']);
});

test('POST reviews returns 422 when body is too short', function (): void {
    $patient = User::factory()->create();
    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/reviews', [
        'rating' => 4,
        'body'   => 'Trumpa',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

test('POST reviews returns 401 when unauthenticated', function (): void {
    $this->postJson('/api/v1/reviews', [
        'rating' => 5,
        'body'   => 'Labai patenkinta apsilankymu klinikoje, rekomenduosiu.',
    ])->assertUnauthorized();
});
