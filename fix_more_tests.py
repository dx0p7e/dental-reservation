#!/usr/bin/env python3

# Fix BackendFixesPass1Test.php - add phone_verified_at to patients that book appointments
with open('tests/Feature/BackendFixesPass1Test.php', 'r') as f:
    content = f.read()

# All 3 failing tests have Patient created with just ['role' => 'patient']
# Only these specific ones post to /api/v1/appointments
content = content.replace(
    "test('slot-based booking stores correct discount_pct and final_price for non-zero tier', function (): void {\n    $patient = User::factory()->create(['role' => 'patient']);",
    "test('slot-based booking stores correct discount_pct and final_price for non-zero tier', function (): void {\n    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);"
)
content = content.replace(
    "test('slot-based booking with standard tier stores discount_pct 0 and final_price equal to service price', function (): void {\n    $patient = User::factory()->create(['role' => 'patient']);",
    "test('slot-based booking with standard tier stores discount_pct 0 and final_price equal to service price', function (): void {\n    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);"
)
content = content.replace(
    "test('request-based booking stores discount_pct 0 and null final_price', function (): void {\n    $patient = User::factory()->create(['role' => 'patient']);",
    "test('request-based booking stores discount_pct 0 and null final_price', function (): void {\n    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);"
)

with open('tests/Feature/BackendFixesPass1Test.php', 'w') as f:
    f.write(content)

print('BackendFixesPass1Test done')

# Fix EmailSmsNotificationsTest.php
with open('tests/Feature/EmailSmsNotificationsTest.php', 'r') as f:
    content = f.read()

content = content.replace(
    "it('dispatches AppointmentBookedNotification after store()', function (): void {\n    $patient = User::factory()->create();",
    "it('dispatches AppointmentBookedNotification after store()', function (): void {\n    $patient = User::factory()->create(['phone_verified_at' => now()]);"
)
content = content.replace(
    "it('dispatches AppointmentRequestedNotification after requestStore()', function (): void {\n    $patient = User::factory()->create();",
    "it('dispatches AppointmentRequestedNotification after requestStore()', function (): void {\n    $patient = User::factory()->create(['phone_verified_at' => now()]);"
)

with open('tests/Feature/EmailSmsNotificationsTest.php', 'w') as f:
    f.write(content)

print('EmailSmsNotificationsTest done')
