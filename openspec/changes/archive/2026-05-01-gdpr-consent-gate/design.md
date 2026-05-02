# Design — GDPR Consent Gate at Registration

## Architecture Overview

The change is a vertical slice through:

1. **API validation layer** (`RegisterRequest`) — rejects requests with no consent
2. **API controller** (`AuthController`) — persists consent timestamp
3. **Frontend store** (`auth.ts`) — forwards consent in the POST payload
4. **Frontend view** (`RegisterView.vue`) — collects consent via a checkbox
5. **Privacy stub page** (`PrivacyView.vue` + router) — gives the checkbox a link target
6. **Seeder** (`AdminUserSeeder`) — ensures no demo user has a null consent timestamp

No migration is required. The `gdpr_consent_at` column already exists in `users`.

## API Layer

### Validation: `RegisterRequest`

Add a single rule to the existing `rules()` array:

```php
'gdpr_consent' => ['required', 'accepted'],
```

`accepted` validates that the value is `"1"`, `"yes"`, `"on"`, or `true`. This means passing `gdpr_consent: true` from the frontend satisfies the rule. No new request class is needed.

### Controller: `AuthController::register()`

The controller calls `User::create($request->validated())`. Because `gdpr_consent_at` is NOT in `$fillable`, it cannot be mass-assigned and the `gdpr_consent` boolean from the request must not be assigned directly to the model — it is a signal, not a column value.

After `User::create()`, set the timestamp explicitly:

```php
$user = User::create($request->validated());
$user->gdpr_consent_at = now();
$user->save();
```

`$request->validated()` will include the `gdpr_consent` boolean in the created user's data — Laravel's mass-assignment protection will silently drop it since `gdpr_consent` is not a column and not in `$fillable`, so `User::create()` is safe. The timestamp is then written via a direct property assignment that bypasses the fillable guard.

## Frontend Store

`resources/spa/stores/auth.ts` — `register()` signature:

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

Add `gdpr_consent: gdprConsent` to the POST payload object. The parameter is `boolean | undefined`; when `true` the backend `accepted` rule is satisfied.

## Frontend View

`RegisterView.vue` uses individual `ref()` fields, not a form object. Pattern to follow:

```typescript
const gdprConsent = ref(false)
```

The checkbox template block (above the submit `<button>`):

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

Pass `gdprConsent.value` as the sixth argument to `authStore.register()`:

```typescript
await authStore.register(
  name.value,
  email.value,
  password.value,
  passwordConfirmation.value,
  phone.value || undefined,
  gdprConsent.value,
)
```

## Privacy Stub Page

`PrivacyView.vue` is a minimal standalone page (no `AppNavbar`) with one informational paragraph. It has no auth guard. No navbar link is added for it.

Router entry (no `name` — consistent with all existing public routes):

```typescript
{ path: '/privacy', component: () => import('@spa/views/PrivacyView.vue') }
```

## Seeder

`AdminUserSeeder::run()` creates the admin user with `User::firstOrCreate()`. Add `'gdpr_consent_at' => now()` to the attribute array (the second argument to `firstOrCreate`). Because `gdpr_consent_at` is not in `$fillable`, use the same pattern as the controller: set the property after creation.

```php
$admin->gdpr_consent_at = now();
$admin->save();
```

## Testing Strategy

New file: `tests/Feature/GdprConsentRegistrationTest.php`

The existing `tests/Feature/Auth/RegistrationTest.php` tests the Fortify web registration route — it is unrelated to the API endpoint and must not be modified.

Tests to write:

1. **`registration requires gdpr consent`** — POST to `/api/v1/auth/register` without `gdpr_consent`; assert 422 with `errors.gdpr_consent` present
2. **`registration with gdpr consent records timestamp`** — POST with `gdpr_consent: true`; assert 201 and `gdpr_consent_at` is not null on the created user
3. **`registration without gdpr consent returns validation error`** — POST with `gdpr_consent: false`; assert 422

## What Is Not Changing

- Login, logout, Fortify routes, two-factor auth, passkeys — untouched
- Filament admin panel — untouched
- `User::$fillable` — no change; direct property assignment handles `gdpr_consent_at`
- No new navbar link for `/privacy`
- No new migration
