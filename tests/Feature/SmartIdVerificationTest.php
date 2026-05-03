<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Notification\NotificationAuthenticationRequestBuilder;
use Sk\SmartId\Notification\NotificationAuthenticationSession;
use Sk\SmartId\Session\SessionStatus;
use Sk\SmartId\Session\SessionStatusPoller;
use Sk\SmartId\SmartIdClient;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    RateLimiter::clear('smart-id-initiate');
});

// ─── POST /api/v1/smart-id/initiate ──────────────────────────────────────────

test('successful initiate returns 200 with verification_code and polling_token', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $session = Mockery::mock(NotificationAuthenticationSession::class);
    $session->allows('getSessionId')->andReturn('test-session-id-123');
    $session->allows('getVerificationCode')->andReturn('1234');

    $builder = Mockery::mock(NotificationAuthenticationRequestBuilder::class);
    $builder->allows('withSemanticsIdentifier')->andReturnSelf();
    $builder->allows('withAllowedInteractionsOrder')->andReturnSelf();
    $builder->allows('initiate')->andReturn($session);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('createNotificationAuthentication')->andReturn($builder);

    $this->postJson('/api/v1/smart-id/initiate', [
        'personal_code' => '40504040001',
        'country' => 'LT',
    ])
        ->assertOk()
        ->assertJsonStructure(['verification_code', 'polling_token'])
        ->assertJsonFragment(['verification_code' => '1234']);
});

test('validation rejects missing personal_code', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/smart-id/initiate', [
        'country' => 'LT',
    ])->assertUnprocessable()->assertJsonValidationErrors(['personal_code']);
});

test('validation rejects invalid personal_code for LT (not 11 digits)', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/smart-id/initiate', [
        'personal_code' => '1234',
        'country' => 'LT',
    ])->assertUnprocessable()->assertJsonValidationErrors(['personal_code']);
});

test('validation rejects unsupported country', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/smart-id/initiate', [
        'personal_code' => '40504040001',
        'country' => 'US',
    ])->assertUnprocessable()->assertJsonValidationErrors(['country']);
});

test('initiate requires authentication', function (): void {
    $this->postJson('/api/v1/smart-id/initiate', [
        'personal_code' => '40504040001',
        'country' => 'LT',
    ])->assertUnauthorized();
});

// ─── GET /api/v1/smart-id/poll/{token} ───────────────────────────────────────

test('poll returns 404 for unknown token', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/smart-id/poll/nonexistent-token')
        ->assertNotFound();
});

test('poll returns 403 for token belonging to another user', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    Cache::put('smart_id_poll_secret-token', [
        'sessionId' => 'sid-123',
        'userId' => $owner->id,
    ], now()->addMinutes(3));

    Sanctum::actingAs($attacker);

    $this->getJson('/api/v1/smart-id/poll/secret-token')
        ->assertForbidden();
});

test('poll returns running when session is in progress', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Cache::put('smart_id_poll_run-token', [
        'sessionId' => 'sid-running',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $status = Mockery::mock(SessionStatus::class);
    $status->allows('isRunning')->andReturn(true);

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-running')->andReturn($status);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/run-token')
        ->assertOk()
        ->assertJsonFragment(['status' => 'running']);
});

test('poll returns ok and sets smart_id_verified_at when session succeeds', function (): void {
    $user = User::factory()->create(['smart_id_verified_at' => null]);
    Sanctum::actingAs($user);

    Cache::put('smart_id_poll_ok-token', [
        'sessionId' => 'sid-ok',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $status = Mockery::mock(SessionStatus::class);
    $status->allows('isRunning')->andReturn(false);
    $status->allows('isComplete')->andReturn(true);

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-ok')->andReturn($status);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/ok-token')
        ->assertOk()
        ->assertJsonFragment(['status' => 'ok']);

    expect($user->fresh()->smart_id_verified_at)->not->toBeNull();
    expect(Cache::has('smart_id_poll_ok-token'))->toBeFalse();
});

test('poll returns failed with reason refused when user refuses', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Cache::put('smart_id_poll_refused-token', [
        'sessionId' => 'sid-refused',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-refused')->andThrow(new UserRefusedException('User refused'));

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/refused-token')
        ->assertOk()
        ->assertJsonFragment(['status' => 'failed', 'reason' => 'refused']);
});

test('poll returns failed with reason timeout when session times out', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Cache::put('smart_id_poll_timeout-token', [
        'sessionId' => 'sid-timeout',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-timeout')->andThrow(new SessionTimeoutException('Timed out'));

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/timeout-token')
        ->assertOk()
        ->assertJsonFragment(['status' => 'failed', 'reason' => 'timeout']);
});

// ─── Rate limiting ────────────────────────────────────────────────────────────

test('rate limit returns 429 after 3 initiate attempts', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $session = Mockery::mock(NotificationAuthenticationSession::class);
    $session->allows('getSessionId')->andReturn('sid-rl');
    $session->allows('getVerificationCode')->andReturn('9999');

    $builder = Mockery::mock(NotificationAuthenticationRequestBuilder::class);
    $builder->allows('withSemanticsIdentifier')->andReturnSelf();
    $builder->allows('withAllowedInteractionsOrder')->andReturnSelf();
    $builder->allows('initiate')->andReturn($session);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('createNotificationAuthentication')->andReturn($builder);

    $payload = ['personal_code' => '40504040001', 'country' => 'LT'];

    $this->postJson('/api/v1/smart-id/initiate', $payload)->assertOk();
    $this->postJson('/api/v1/smart-id/initiate', $payload)->assertOk();
    $this->postJson('/api/v1/smart-id/initiate', $payload)->assertOk();
    $this->postJson('/api/v1/smart-id/initiate', $payload)->assertTooManyRequests();
});

test('initiate returns 422 when personal code is not registered in Smart-ID', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $builder = Mockery::mock(NotificationAuthenticationRequestBuilder::class);
    $builder->allows('withSemanticsIdentifier')->andReturnSelf();
    $builder->allows('withAllowedInteractionsOrder')->andReturnSelf();
    $builder->allows('initiate')->andThrow(new SessionNotFoundException('Not found'));

    $client = $this->mock(SmartIdClient::class);
    $client->allows('createNotificationAuthentication')->andReturn($builder);

    $this->postJson('/api/v1/smart-id/initiate', ['personal_code' => '50307051462', 'country' => 'LT'])
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Personal code is not registered with Smart-ID.']);
});

// ─── Bulk-confirm pending appointments on verification ────────────────────────────────────────

test('poll confirms all pending appointments when Smart-ID verification succeeds', function (): void {
    $user = User::factory()->create(['smart_id_verified_at' => null]);
    Sanctum::actingAs($user);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $pending1 = Appointment::factory()->create([
        'patient_id' => $user->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Pending,
    ]);
    $pending2 = Appointment::factory()->create([
        'patient_id' => $user->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Pending,
    ]);
    $cancelled = Appointment::factory()->create([
        'patient_id' => $user->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Cancelled,
    ]);

    Cache::put('smart_id_poll_bulk-token', [
        'sessionId' => 'sid-bulk',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $status = Mockery::mock(SessionStatus::class);
    $status->allows('isRunning')->andReturn(false);
    $status->allows('isComplete')->andReturn(true);

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-bulk')->andReturn($status);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/bulk-token')->assertOk();

    expect($pending1->fresh()->status)->toBe(AppointmentStatus::Confirmed);
    expect($pending2->fresh()->status)->toBe(AppointmentStatus::Confirmed);
    expect($cancelled->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('poll does not affect appointments of other users when verification succeeds', function (): void {
    $user = User::factory()->create(['smart_id_verified_at' => null]);
    $otherUser = User::factory()->create();
    Sanctum::actingAs($user);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $otherAppointment = Appointment::factory()->create([
        'patient_id' => $otherUser->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Pending,
    ]);

    Cache::put('smart_id_poll_other-token', [
        'sessionId' => 'sid-other',
        'userId' => $user->id,
    ], now()->addMinutes(3));

    $status = Mockery::mock(SessionStatus::class);
    $status->allows('isRunning')->andReturn(false);
    $status->allows('isComplete')->andReturn(true);

    $poller = Mockery::mock(SessionStatusPoller::class);
    $poller->allows('poll')->with('sid-other')->andReturn($status);

    $client = $this->mock(SmartIdClient::class);
    $client->allows('setPollTimeoutMs')->with(0);
    $client->allows('getSessionStatusPoller')->andReturn($poller);

    $this->getJson('/api/v1/smart-id/poll/other-token')->assertOk();

    expect($otherAppointment->fresh()->status)->toBe(AppointmentStatus::Pending);
});
