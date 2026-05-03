## Why

The patient profile currently offers only email and phone OTP verification, which are sufficient for booking access but do not confirm a patient's real-world identity. Adding Smart-ID verification as an optional, additive step gives the clinic a higher-trust identity signal — the kind expected from a professional healthcare provider — using the national eID infrastructure already familiar to Lithuanian patients.

## What Changes

- New "Tapatybės patvirtinimas" section added to the patient profile page — visible only when `smart_id_verified_at` is null.
- Patient enters their asmens kodas (personal identification number) and country, triggers a Smart-ID push notification, sees a verification code on screen, and approves in the Smart-ID app.
- On successful validation, `smart_id_verified_at` is set on the user record.
- Once verified, the section shows a "✓ Patvirtinta" badge with the verification date instead of the form.
- Smart-ID verification status shown in the Filament admin user table and edit form.
- New `sk-id-solutions/smart-id-php-client` composer dependency added.
- Demo environment used during development (`sid.demo.sk.ee`); production endpoint configured via `.env`.

## Capabilities

### New Capabilities
- `smart-id-identity-verification`: Patient can verify their real-world identity via Smart-ID from the profile page. Stores `smart_id_verified_at` on the user. Does not affect login, registration, or the booking gate.

### Modified Capabilities
<!-- No existing capability requirements change — phone/email verification gate is untouched -->

## Impact

- `database/migrations/` — new migration: `add_smart_id_verified_at_to_users_table`
- `app/Models/User.php` — add `smart_id_verified_at` cast
- `app/Http/Controllers/Api/V1/SmartIdVerificationController.php` — new controller (initiate, poll)
- `routes/api.php` — two new authenticated routes
- `config/smart-id.php` — new config file (RP UUID, RP name, host URL)
- `.env` / `.env.example` — `SMARTID_RP_UUID`, `SMARTID_RP_NAME`, `SMARTID_HOST_URL`
- `resources/spa/views/ProfileView.vue` — new Smart-ID section
- `app/Filament/Resources/Users/Tables/UsersTable.php` — `smart_id_verified_at` column
- `app/Filament/Resources/Users/` — edit form field
- `composer.json` — `sk-id-solutions/smart-id-php-client`
