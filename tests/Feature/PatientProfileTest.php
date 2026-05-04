<?php

use App\Models\Doctor;
use App\Models\PhoneVerification;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Notification::fake();
});

test('GET profile returns correct fields for authenticated user', function (): void {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'phone' => '+37060000001',
        'notification_channel' => 'sms',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonFragment([
            'name' => 'Jane Doe',
            'phone' => '+37060000001',
            'notification_channel' => 'sms',
        ])
        ->assertJsonStructure(['id', 'name', 'email', 'phone', 'email_verified_at', 'phone_verified_at', 'notification_channel']);
});

// ─── PATCH /api/v1/profile ────────────────────────────────────────────────────

test('PATCH profile updates name, phone, and notification_channel', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'Updated Name',
        'email' => $user->email,
        'phone' => '+37060000001',
        'notification_channel' => 'both',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->notification_channel)->toBe('both');
});

test('PATCH profile clears phone_verified_at when phone changes', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '+37060000099',
    ])->assertOk();

    expect($user->fresh()->phone_verified_at)->toBeNull();
});

test('PATCH profile does not clear phone_verified_at when only name changes', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => now()->subDay()]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'New Name Only',
        'email' => $user->email,
        'phone' => '+37060000001',
    ])->assertOk();

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

// ─── PATCH /api/v1/profile/password ──────────────────────────────────────────

test('PATCH profile/password rejects wrong current_password with 422', function (): void {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertUnprocessable();
});

test('PATCH profile/password updates password when current_password is correct', function (): void {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'correct-password',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();
});

// ─── POST /api/v1/email/verification-notification ────────────────────────────

test('POST email/verification-notification sends notification when email is unverified', function (): void {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('POST email/verification-notification returns 200 when already verified without resending', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/email/verification-notification')
        ->assertOk();

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

// ─── POST /api/v1/phone/send-otp ─────────────────────────────────────────────

test('POST phone/send-otp returns 422 when phone is null', function (): void {
    $user = User::factory()->create(['phone' => null]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/phone/send-otp')
        ->assertUnprocessable();
});

test('POST phone/send-otp creates a PhoneVerification row and sends no real notification in testing env', function (): void {
    // APP_ENV=testing hits the log-only branch — no Vonage notification is sent
    $user = User::factory()->create(['phone' => '+37060000001']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/phone/send-otp')->assertOk();

    expect(PhoneVerification::where('user_id', $user->id)->exists())->toBeTrue();
    Notification::assertNothingSent();
});

// ─── POST /api/v1/phone/verify-otp ───────────────────────────────────────────

test('POST phone/verify-otp with valid code sets phone_verified_at and deletes OTP row', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => null]);
    Sanctum::actingAs($user);

    PhoneVerification::create([
        'user_id' => $user->id,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
    ]);

    $this->postJson('/api/v1/phone/verify-otp', ['code' => '123456'])
        ->assertOk();

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
    expect(PhoneVerification::where('user_id', $user->id)->exists())->toBeFalse();
});

test('POST phone/verify-otp with expired code returns 422', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => null]);
    Sanctum::actingAs($user);

    PhoneVerification::create([
        'user_id' => $user->id,
        'code' => '999999',
        'expires_at' => now()->subMinute(),
        'created_at' => now()->subMinutes(11),
    ]);

    $this->postJson('/api/v1/phone/verify-otp', ['code' => '999999'])
        ->assertUnprocessable();
});

test('POST phone/verify-otp with wrong code returns 422', function (): void {
    $user = User::factory()->create(['phone' => '+37060000001', 'phone_verified_at' => null]);
    Sanctum::actingAs($user);

    PhoneVerification::create([
        'user_id' => $user->id,
        'code' => '111111',
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
    ]);

    $this->postJson('/api/v1/phone/verify-otp', ['code' => '999999'])
        ->assertUnprocessable();
});

// ─── Booking gate ─────────────────────────────────────────────────────────────

test('POST appointments returns 403 when email is unverified', function (): void {
    $user = User::factory()->unverified()->create(['phone_verified_at' => now()]);
    $user->assignRole('patient');
    Sanctum::actingAs($user);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertForbidden();
});

test('POST appointments returns 403 when phone is unverified', function (): void {
    $user = User::factory()->create(['phone_verified_at' => null]);
    $user->assignRole('patient');
    Sanctum::actingAs($user);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertForbidden();
});

test('POST appointments proceeds when both email and phone are verified', function (): void {
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $user->assignRole('patient');
    Sanctum::actingAs($user);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $this->postJson('/api/v1/appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'slot_id' => $slot->id,
    ])->assertCreated();
});

test('POST appointments/request returns 403 when either verification is missing', function (): void {
    $user = User::factory()->create(['phone_verified_at' => null]);
    $user->assignRole('patient');
    Sanctum::actingAs($user);

    $service = Service::factory()->create();

    $this->postJson('/api/v1/appointments/request', [
        'service_id' => $service->id,
        'preferred_date' => now()->addWeek()->toDateString(),
    ])->assertForbidden();
});

// ─── Email verification redirect ─────────────────────────────────────────────

test('GET email verify redirects to /profile?verified=1 on success', function (): void {
    $user = User::factory()->unverified()->create();
    $hash = sha1($user->email);
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => $hash],
    );

    $this->get($url)->assertRedirectContains('/profile?verified=1');
});

test('GET email verify redirects to /profile?error=invalid_link on bad hash', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'badhash'],
    );

    $this->get($url)->assertRedirectContains('/profile?error=invalid_link');
});
