## Why

The `users` table has a `gdpr_consent_at` column that is never written to. The registration endpoint accepts no consent signal and the backend ignores the field entirely, meaning every user record has a null consent timestamp. This change closes that gap: registration is gated behind an explicit consent checkbox, and the timestamp is recorded on sign-up.

## What Changes

- **`RegisterRequest` validation** — new rule `'gdpr_consent' => ['required', 'accepted']` so registration returns 422 without consent
- **`AuthController::register()`** — sets `gdpr_consent_at = now()` on the newly-created user after `User::create()`
- **`authStore.register()`** in `resources/spa/stores/auth.ts` — signature extended with a `gdprConsent: boolean` parameter; value forwarded in the POST payload as `gdpr_consent`
- **`RegisterView.vue`** — consent checkbox added above the submit button, bound to a `gdprConsent` ref, error display for `errors.gdpr_consent`, and the checkbox value passed to `authStore.register()`
- **`AdminUserSeeder`** — sets `gdpr_consent_at: now()` on the seeded admin user so no demo account has a null consent timestamp
- **`PrivacyView.vue`** — new stub page at `/privacy` with a placeholder paragraph (no auth guard, no navbar link)
- **Router** — new public route `{ path: '/privacy', component: () => import('@spa/views/PrivacyView.vue') }` (no `name`, consistent with all existing routes)

## Capabilities

### New Capabilities
- `gdpr-consent-registration`: Registration endpoint and UI enforce explicit GDPR consent before account creation

### Modified Capabilities
- `registration-flow`: Existing registration form and API extended with consent field (new validation rule, new payload field, new UI element)

## Impact

- `app/Http/Requests/Api/V1/RegisterRequest.php` — add `gdpr_consent` validation rule
- `app/Http/Controllers/Api/V1/AuthController.php` — set `gdpr_consent_at` after user creation
- `resources/spa/stores/auth.ts` — extend `register()` signature
- `resources/spa/views/RegisterView.vue` — add checkbox + error display + pass consent to store
- `resources/spa/views/PrivacyView.vue` — new stub file
- `resources/spa/router/index.ts` — new `/privacy` route entry
- `database/seeders/AdminUserSeeder.php` — set `gdpr_consent_at` on the admin seed user
- No new migration needed — column already exists
- No change to login, logout, Filament admin, or any other auth endpoint
