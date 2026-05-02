# Tasks — GDPR Consent Gate at Registration

## Task 1 — Add `gdpr_consent` validation rule to `RegisterRequest`

File: `app/Http/Requests/Api/V1/RegisterRequest.php`

Add to `rules()`:

```php
'gdpr_consent' => ['required', 'accepted'],
```

## Task 2 — Record `gdpr_consent_at` in `AuthController::register()`

File: `app/Http/Controllers/Api/V1/AuthController.php`

After `User::create($request->validated())`, add:

```php
$user->gdpr_consent_at = now();
$user->save();
```

Note: `gdpr_consent_at` is not in `$fillable`, so direct property assignment is used. The `gdpr_consent` boolean from `$request->validated()` is silently dropped by mass-assignment protection — this is expected.

## Task 3 — Extend `authStore.register()` to forward consent

File: `resources/spa/stores/auth.ts`

Update `register()` signature to:

```typescript
async function register(
  name: string,
  email: string,
  password: string,
  passwordConfirmation: string,
  phone?: string,
  gdprConsent?: boolean,
)
```

Add `gdpr_consent: gdprConsent` to the POST payload object.

Update the `return` statement to expose the updated function (it is already exported via `return { ..., register, ... }`).

## Task 4 — Add consent checkbox and ref to `RegisterView.vue`

File: `resources/spa/views/RegisterView.vue`

1. Add `const gdprConsent = ref(false)` alongside the other individual field refs.

2. Insert the checkbox block above the submit `<button>`:

```html
<div>
  <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
    <input
      v-model="gdprConsent"
      type="checkbox"
      class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600"
    />
    <span>
      I agree to the
      <a href="/privacy" target="_blank" class="text-blue-600 hover:underline">Privacy Policy</a>
      and consent to processing of my personal data.
    </span>
  </label>
  <p v-if="errors.gdpr_consent" class="mt-1 text-xs text-red-600">{{ errors.gdpr_consent[0] }}</p>
</div>
```

3. Update `handleSubmit()` to pass `gdprConsent.value` as the sixth argument to `authStore.register()`.

## Task 5 — Create `PrivacyView.vue`

File: `resources/spa/views/PrivacyView.vue`

A standalone page (no `AppNavbar`) with:
- A heading: `Privacy Policy`
- A placeholder paragraph explaining that the full policy will be published here

No auth guard. No navbar link. Minimal styling consistent with the existing public pages.

## Task 6 — Add `/privacy` route to the SPA router

File: `resources/spa/router/index.ts`

Add a public route entry (no `name` — consistent with all other routes):

```typescript
{ path: '/privacy', component: () => import('@spa/views/PrivacyView.vue') }
```

## Task 7 — Set `gdpr_consent_at` on the seeded admin user

File: `database/seeders/AdminUserSeeder.php`

After `$admin->assignRole('admin')`, add:

```php
if (is_null($admin->gdpr_consent_at)) {
    $admin->gdpr_consent_at = now();
    $admin->save();
}
```

The null check ensures re-running the seeder does not overwrite an existing timestamp.

## Task 8 — Write feature tests

File: `tests/Feature/GdprConsentRegistrationTest.php`

Create via: `php artisan make:test --pest GdprConsentRegistrationTest`

Write three tests:

1. **`registration requires gdpr consent`** — POST `/api/v1/auth/register` with all valid fields but without `gdpr_consent`; assert status 422, assert `errors.gdpr_consent` exists.

2. **`registration with gdpr consent records timestamp`** — POST with all valid fields and `gdpr_consent: true`; assert status 201; assert the created user's `gdpr_consent_at` is not null.

3. **`registration with gdpr consent false is rejected`** — POST with `gdpr_consent: false`; assert status 422, assert `errors.gdpr_consent` exists.

## Task 9 — Run Pint

```bash
vendor/bin/pint --dirty --format agent
```

## Task 10 — Run tests

```bash
php artisan test --compact --filter=GdprConsent
```

All three tests must pass.
