#!/usr/bin/env python3

with open('tests/Feature/Api/V1/AppointmentApiTest.php', 'r') as f:
    content = f.read()

# Fix 'patient can create a booking' - add phone_verified_at
content = content.replace(
    "test('patient can create a booking', function (): void {\n    $patient = User::factory()->create();",
    "test('patient can create a booking', function (): void {\n    $patient = User::factory()->create(['phone_verified_at' => now()]);"
)

# Fix 'double-booking returns 404' - add phone_verified_at
content = content.replace(
    "test('double-booking returns 404', function (): void {\n    $patient = User::factory()->create();",
    "test('double-booking returns 404', function (): void {\n    $patient = User::factory()->create(['phone_verified_at' => now()]);"
)

# Fix 'patient can submit a booking request' - add phone_verified_at
content = content.replace(
    "test('patient can submit a booking request', function (): void {\n    $patient = User::factory()->create();",
    "test('patient can submit a booking request', function (): void {\n    $patient = User::factory()->create(['phone_verified_at' => now()]);"
)

with open('tests/Feature/Api/V1/AppointmentApiTest.php', 'w') as f:
    f.write(content)

print('Done')
