# Tasks: Smart-ID Identity Verification

## 1. Dependency & Config

- [x] 1.1 Run `composer require sk-id-solutions/smart-id-php-client --no-interaction` to install the official Smart-ID PHP SDK
- [x] 1.2 Create `config/smart-id.php` with keys: `rp_uuid` (env `SMARTID_RP_UUID`, default `00000000-0000-4000-8000-000000000000`), `rp_name` (env `SMARTID_RP_NAME`, default `DEMO`), `host_url` (env `SMARTID_HOST_URL`, default `https://sid.demo.sk.ee/smart-id-rp/v3`), `ocsp_enabled` (env `SMARTID_OCSP_ENABLED`, default `false`)
- [x] 1.3 Add `SMARTID_RP_UUID`, `SMARTID_RP_NAME`, `SMARTID_HOST_URL`, `SMARTID_OCSP_ENABLED` to `.env.example` with demo defaults

## 2. Migration & Model

- [x] 2.1 Run `php artisan make:migration add_smart_id_verified_at_to_users_table --no-interaction` and add `$table->timestamp('smart_id_verified_at')->nullable()->after('phone_verified_at')` in `up()` and `$table->dropColumn('smart_id_verified_at')` in `down()`
- [x] 2.2 Run `php artisan migrate --no-interaction`
- [x] 2.3 In `app/Models/User.php`, add `'smart_id_verified_at' => 'datetime'` to the `casts()` array and `'smart_id_verified_at'` to the `$fillable` array

## 3. Backend Controller & Routes

- [x] 3.1 Run `php artisan make:controller Api/V1/SmartIdVerificationController --no-interaction` and implement two public methods: `initiate(Request $request): JsonResponse` and `poll(Request $request, string $token): JsonResponse`
- [x] 3.2 Implement `initiate()`: validate `personal_code` (required, string, regex `/^\d{11}$/` for LT, or `max:20` for non-LT) and `country` (required, in: LT, EE, LV); construct a `SmartIdClient` using `config('smart-id.*')`; call notification-based auth with `SemanticsIdentifier::forPerson($country, $personalCode)`; generate a random 32-char `$pollingToken`; store `['sessionId' => $session->getSessionId(), 'userId' => $request->user()->id]` in `Cache::put("smart_id_poll_{$pollingToken}", ..., now()->addMinutes(3))`; return `['verification_code' => $session->getVerificationCode(), 'polling_token' => $pollingToken]`
- [x] 3.3 Implement `poll()`: retrieve the cache entry for `"smart_id_poll_{$token}"`; if missing return 404; if `userId` does not match `$request->user()->id` return 403; perform a single `$poller->poll($sessionId)` call; if `isRunning()` return `['status' => 'running']`; if complete and `endResult === 'OK'` then `$request->user()->update(['smart_id_verified_at' => now()])`, delete cache key, return `['status' => 'ok']`; on user refusal return `['status' => 'failed', 'reason' => 'refused']`; on timeout return `['status' => 'failed', 'reason' => 'timeout']`; catch all other `SmartIdException` and return `['status' => 'failed', 'reason' => 'error']`
- [x] 3.4 In `routes/api.php`, inside the `auth:sanctum` middleware group, add:
  - `Route::post('smart-id/initiate', [V1SmartIdVerificationController::class, 'initiate'])->middleware('throttle:3,5')->name('smart-id.initiate')`
  - `Route::get('smart-id/poll/{token}', [V1SmartIdVerificationController::class, 'poll'])->name('smart-id.poll')`
  and add the corresponding `use` import for `SmartIdVerificationController`

## 4. Frontend — ProfileView.vue

- [x] 4.1 In `fetchProfile()`, extend the destructured response to include `smart_id_verified_at` and store it in a new `smartIdVerifiedAt` ref (nullable string)
- [x] 4.2 Add new refs for Smart-ID state: `smartIdPersonalCode` (string), `smartIdCountry` (string, default `'LT'`), `smartIdLoading` (boolean), `smartIdPollingToken` (string | null), `smartIdVerificationCode` (string | null), `smartIdPolling` (boolean), `smartIdError` (string), `smartIdPollingInterval` (ReturnType<typeof setInterval> | null)
- [x] 4.3 Implement `initiateSmartId()`: set `smartIdLoading = true`, POST to `/smart-id/initiate` with `{ personal_code, country }`, on success set `smartIdVerificationCode` and `smartIdPollingToken` and start polling interval (every 2000ms calling `pollSmartId()`), catch errors and set `smartIdError`
- [x] 4.4 Implement `pollSmartId()`: GET `/smart-id/poll/{smartIdPollingToken}`; on `status: 'ok'` clear interval, set `smartIdVerifiedAt = new Date().toISOString()`, reset polling state; on `status: 'failed'` clear interval, set `smartIdError` based on reason; on `status: 'running'` do nothing
- [x] 4.5 Add Smart-ID section template after the phone verification section: show verified badge (`smartIdVerifiedAt != null`), or initiation form (country select LT/EE/LV, personal code input, submit button), or polling state (verification code displayed large, spinner, cancel button that clears the interval)
- [x] 4.6 Add i18n keys to `resources/spa/locales/lt.json` and `resources/spa/locales/en.json` for all new Smart-ID section labels (section title, personal code label, country label, verification code instruction, loading text, verified text, error messages for refused/timeout/error)

## 5. Filament Admin

- [x] 5.1 In `app/Filament/Resources/Users/Tables/UsersTable.php`, add a `TextColumn::make('smart_id_verified_at')` column with `->label('Smart-ID')`, `->badge()`, `->formatStateUsing(fn ($state) => $state ? 'Patvirtinta' : 'Nepatvirtinta')`, `->color(fn ($state) => $state ? 'success' : 'gray')`, `->sortable()`
- [x] 5.2 In `app/Filament/Resources/Users/Schemas/UserForm.php`, add a `Placeholder::make('smart_id_verified_at')->label('Smart-ID patvirtinta')->content(fn (User $record) => $record->smart_id_verified_at?->format('Y-m-d H:i') ?? 'Nepatvirtinta')->hiddenOn('create')` to the form schema

## 6. Tests

- [x] 6.1 Run `php artisan make:test --pest SmartIdVerificationTest --no-interaction` and write feature tests covering: successful initiate returns 200 with verification_code and polling_token; validation rejects missing/invalid personal_code; validation rejects unsupported country; poll returns running when session is running; poll returns ok and sets smart_id_verified_at when session succeeds; poll returns failed with reason on user refusal; poll returns 404 for unknown token; poll returns 403 for token belonging to another user; rate limit returns 429 after 3 attempts
- [x] 6.2 Run `php artisan test --compact --filter=SmartIdVerificationTest` and confirm all tests pass

## 7. Code Style

- [x] 7.1 Run `vendor/bin/pint --dirty --format agent` and fix any style issues
