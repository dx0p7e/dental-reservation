# Tasks — GDPR Consent Gate at Registration

## 1. Backend Validation

- [x] 1.1 Add gdpr_consent rule to RegisterRequest rules()

## 2. Backend Controller

- [x] 2.1 Set gdpr_consent_at on user after User::create() in AuthController::register()

## 3. Frontend Store

- [x] 3.1 Add gdprConsent optional boolean parameter to register() in auth.ts
- [x] 3.2 Add gdpr_consent field to POST payload in register()

## 4. Frontend Registration View

- [x] 4.1 Add gdprConsent ref to RegisterView.vue script section
- [x] 4.2 Add consent checkbox block above the submit button
- [x] 4.3 Pass gdprConsent.value as sixth argument to authStore.register()

## 5. Privacy Stub Page

- [x] 5.1 Create PrivacyView.vue with Privacy Policy heading and placeholder paragraph

## 6. Router

- [x] 6.1 Add /privacy route to resources/spa/router/index.ts

## 7. Seeder

- [x] 7.1 Set gdpr_consent_at on admin user in AdminUserSeeder if currently null

## 8. Tests

- [x] 8.1 Create GdprConsentRegistrationTest via artisan make:test
- [x] 8.2 Test: registration requires gdpr consent returns 422
- [x] 8.3 Test: registration with consent records timestamp, returns 201
- [x] 8.4 Test: registration with gdpr_consent false returns 422

## 9. Code Style

- [x] 9.1 Run pint --dirty

## 10. Verify

- [x] 10.1 Run tests --filter=GdprConsent and confirm all pass
