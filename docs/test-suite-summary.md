# Test Suite Summary — `dental-reservation`

**Generated:** 2026-05-03  
**Framework:** Pest v4 / PHPUnit v12  
**Language:** PHP 8.4 / Laravel 13

---

## 1. Total Test Count

| Scope | Count |
|---|---|
| Unit | 1 |
| Feature | ~262 |
| **Total** | **~263** |

> Consistent with the most recent run: `0 failed, 22 skipped, 241 passed (569 assertions)`.

---

## 2. Tests Grouped by File

### `tests/Unit/`

| File | Tests | Description |
|---|---|---|
| `ExampleTest.php` | 1 | Sanity check that `true === true`. |

---

### `tests/Feature/Auth/`

| File | Tests | Description |
|---|---|---|
| `AuthenticationTest.php` | 6 | Login screen rendering, successful login, 2FA redirect, failed login, logout, and rate limiting. |
| `RegistrationTest.php` | 2 | Registration screen rendering and successful user creation. |
| `PasswordResetTest.php` | 5 | Password reset link screen, reset link request, reset screen rendering, valid/invalid token reset. |
| `EmailVerificationTest.php` | 6 | Verification screen, successful verification, invalid hash/user-id, redirect for already-verified users. |
| `PasswordConfirmationTest.php` | 1 | Password confirmation screen rendering. |
| `TwoFactorChallengeTest.php` | 2 | Redirect when unauthenticated, and challenge screen rendering. |
| `VerificationNotificationTest.php` | 2 | Resend verification notification for unverified and already-verified users. |

---

### `tests/Feature/Settings/`

| File | Tests | Description |
|---|---|---|
| `ProfileUpdateTest.php` | 5 | Profile display, information update, email verification unchanged on no-change, account deletion with correct/incorrect password. |
| `SecurityTest.php` | 6 | Security page display, password confirmation gate, 2FA feature flag, password change with valid/invalid current password. |

---

### `tests/Feature/Api/V1/`

| File | Tests | Description |
|---|---|---|
| `AuthApiTest.php` | 5 | API register, login, non-patient login rejection, token logout, TransientToken logout edge-case. |
| `AppointmentApiTest.php` | 10 | Slot booking, double-booking, patient scoping, cancel pending/completed, non-owner cancel, booking request, past date/missing service validation, request-based resource shape. |
| `DoctorApiTest.php` | 5 | Active doctors list, null/set `photo_url`, available slot listing, date filter on slots. |
| `LoyaltyApiTest.php` | 4 | Balance and tier response, defaults when no account exists, empty transactions, unauthenticated 401. |
| `ReviewApiTest.php` | 8 | List reviews, name anonymisation, unpublished exclusion, create, duplicate prevention, invalid rating/short body, unauthenticated 401. |

---

### `tests/Feature/Filament/`

| File | Tests | Description |
|---|---|---|
| `AppointmentResourceTest.php` | 6 | Admin appointment list/create/edit pages and patients relation manager in the Filament admin panel. |
| `DoctorResourceTest.php` | 5 | Admin doctor list/create/edit CRUD operations. |
| `DoctorScheduleResourceTest.php` | 7 | Admin schedule list/create/edit, overlap validation on creation, non-overlapping slots allowed. |
| `DoctorScheduleGenerateActionTest.php` | 1 | Generate slots table action creates `ScheduleSlot` records for the selected schedule. |
| `ServiceResourceTest.php` | 5 | Admin service list/create/edit CRUD operations. |
| `LoyaltyTierResourceTest.php` | 5 | Admin loyalty tier list/create/edit CRUD operations. |
| `LoyaltyRuleResourceTest.php` | 5 | Admin loyalty rule list/create/edit CRUD operations. |

---

### `tests/Feature/` (root)

| File | Tests | Description |
|---|---|---|
| `ExampleTest.php` | 1 | Basic HTTP 200 response for the application homepage. |
| `SpaShellRouteTest.php` | 3 | SPA shell returns 200 for homepage, login, and catch-all unknown paths. |
| `DashboardTest.php` | 2 | Guest redirect to login; authenticated user accesses dashboard. |
| `GdprConsentRegistrationTest.php` | 3 | Registration blocked without GDPR consent, timestamp recorded with consent, `false` value rejected. |
| `ContactFormTest.php` | 6 | Guest public API access, contact form success/validation, 405 on GET, rate limiting. |
| `ServicesPageTest.php` | 3 | Public services API structure, unauthenticated access, empty result. |
| `PatientProfileTest.php` | 18 | Full API profile lifecycle: GET fields, PATCH name/phone/channel, phone verification clearing, password change, email/phone OTP flow, booking 403 gates, email verify redirects. |
| `LoyaltyStatusTest.php` | 5 | Loyalty endpoint next-tier progression, highest tier, no tiers, transactions array shape, null service name. |
| `LoyaltyBookingPreviewTest.php` | 11 | Booking preview endpoint pricing, 0% discount, no rule, auth/validation guards, `LoyaltyPricingService` unit logic, multiplicative discounts, expired/inactive rule skipping. |
| `ServiceLoyaltyDiscountTest.php` | 8 | `loyalty_discount_pct` field per tier (unauthenticated, silver, gold, standard, no account), promo rules active/inactive, doctor services endpoint. |
| `AppointmentObserverTest.php` | 8 | Points award on completion, no rule/duplicate/non-completed status skipping, auto-create loyalty account, tier upgrade on threshold crossing. |
| `AppointmentEmailNotificationsTest.php` | 5 | No mail on notes-only change, 23–25h reminder window, `reminder_sent_at` stamp, skip already-reminded, skip Pending/Cancelled. |
| `EmailSmsNotificationsTest.php` | 10 | All 5 appointment lifecycle notification dispatches, notes-only no-dispatch, `via()` returns correct channel for `email`/`sms`/`both`. |
| `AppointmentReschedulingTest.php` | 13 | Reschedule pending/confirmed, ownership guard, no-slot/cancelled/completed/cross-doctor/booked slot rejections, slot `is_booked` toggling, reschedule notification, show owner/non-owner. |
| `BackendFixesPass1Test.php` | 8 | Pricing stored on slot booking (silver/standard tier), request-based null price, resource response keys, no-show action, no-show visibility, slot generation warning notification, confirm-request action pricing. |
| `AuditLogTest.php` | 4 | Activity log entries on loyalty transaction creation, balance update with old/new values, tier change, admin list page render. |
| `ReportsAnalyticsTest.php` | 5 | Admin dashboard render, appointment overview widget for slot-based and request-based appointments, revenue estimate widget sum and zero. |
| `DoctorPanelAccessTest.php` | 7 | `canAccessPanel` for doctor/patient/admin on both panels, redirect into doctor panel for authenticated doctor, redirect to login for unauthenticated. |
| `DoctorScheduleResourceTest.php` | 5 | Doctor-panel schedule list, scoped query, `doctor_id` forced on create, 404 on other doctor's schedule edit, create page load. |
| `DoctorAppointmentResourceTest.php` | 6 | Doctor-panel appointment list, scoped query, save `doctor_notes`, mark complete/no-show, 404 for other doctor's appointment. |
| `SyncDoctorRolesCommandTest.php` | 3 | `doctor:sync-roles` assigns Spatie role to doctor-enum users, skips already-assigned, ignores patients. |
| `GenerateSlotsCommandTest.php` | 3 | `slots:generate` Artisan command for active schedules, skips inactive, respects `--date` and `--days` options. |
| `SlotGenerationServiceTest.php` | 4 | `SlotGenerationService` generates slots, idempotency, slot times match `slot_duration_minutes`, day-of-week mismatch produces zero slots. |
| `DoctorServicePivotTest.php` | 6 | `doctor_service` pivot table existence, doctors API includes services array, empty services, per-doctor services endpoint, 404 for unknown doctor. |
| `UserResourceTest.php` | 11 | Admin user list/create with Spatie role, auto-create doctor profile, role change sync, doctor profile on role edit, reset password, verify email, revoke verifications, self-delete prevention. |
| `TempDebugTest.php` | 2 | Debug access checks for `/doctor` root and `/doctor/doctor-appointments` paths (diagnostic file, likely removable). |

---

## 3. Flat List of All Test Descriptions

### Unit

- `that true is true`

### Auth

- `login screen can be rendered`
- `users can authenticate using the login screen`
- `users with two factor enabled are redirected to two factor challenge`
- `users can not authenticate with invalid password`
- `users can logout`
- `users are rate limited`
- `registration screen can be rendered`
- `new users can register`
- `reset password link screen can be rendered`
- `reset password link can be requested`
- `reset password screen can be rendered`
- `password can be reset with valid token`
- `password cannot be reset with invalid token`
- `email verification screen can be rendered`
- `email can be verified`
- `email is not verified with invalid hash`
- `email is not verified with invalid user id`
- `verified user is redirected to dashboard from verification prompt`
- `already verified user visiting verification link is redirected without firing event again`
- `confirm password screen can be rendered`
- `two factor challenge redirects to login when not authenticated`
- `two factor challenge can be rendered`
- `sends verification notification`
- `does not send verification notification if email is verified`

### Settings

- `profile page is displayed`
- `profile information can be updated`
- `email verification status is unchanged when the email address is unchanged`
- `user can delete their account`
- `correct password must be provided to delete account`
- `security page is displayed`
- `security page requires password confirmation when enabled`
- `security page does not require password confirmation when disabled`
- `security page renders without two factor when feature is disabled`
- `password can be updated`
- `correct password must be provided to update password`

### API v1 — Auth

- `register creates a patient and returns a token`
- `login returns a token for a patient`
- `non-patient login is rejected with 403`
- `logout revokes the token`
- `logout does not crash when authenticated via session guard (TransientToken)`

### API v1 — Appointments

- `patient can create a booking`
- `double-booking returns 404`
- `patient cannot see another patient appointments`
- `patient can cancel a pending appointment`
- `patient cannot cancel a completed appointment`
- `non-owner cancel returns 403`
- `patient can submit a booking request`
- `booking request with past preferred_date is rejected`
- `booking request without authentication is rejected`
- `booking request without service_id is rejected`
- `appointment resource returns null slot and doctor for request-based appointments`

### API v1 — Doctors

- `doctor list returns only active doctors`
- `doctor resource returns null photo_url when photo_path is null`
- `doctor resource returns absolute photo_url when photo_path is set`
- `slots endpoint returns only non-booked slots for a doctor`
- `slots endpoint supports date filter`

### API v1 — Loyalty

- `loyalty endpoint returns balance and tier`
- `loyalty endpoint returns defaults when no account exists`
- `loyalty fallback account response includes an empty transactions array`
- `unauthenticated request returns 401`

### API v1 — Reviews

- `GET reviews returns 200 with data array`
- `GET reviews anonymises patient name to first name and last initial`
- `GET reviews excludes unpublished reviews`
- `POST reviews creates a review and returns 201`
- `POST reviews returns 409 when patient already reviewed`
- `POST reviews returns 422 on invalid rating`
- `POST reviews returns 422 when body is too short`
- `POST reviews returns 401 when unauthenticated`

### Filament — Appointments

- `list page renders`
- `create page renders`
- `can create an appointment`
- `edit page renders`
- `can edit an appointment`
- `patients relation manager renders`

### Filament — Doctors

- `list page renders`
- `create page renders`
- `can create a doctor`
- `edit page renders`
- `can edit a doctor`

### Filament — Doctor Schedules

- `list page renders`
- `create page renders`
- `can create a schedule slot`
- `edit page renders`
- `can edit a schedule slot`
- `overlapping slot on create is blocked`
- `non-overlapping slots on different days are allowed`
- `generate slots action creates slots for the schedule`

### Filament — Services

- `list page renders`
- `create page renders`
- `can create a service`
- `edit page renders`
- `can edit a service`

### Filament — Loyalty Tiers

- `list page renders`
- `create page renders`
- `can create a loyalty tier`
- `edit page renders`
- `can edit a loyalty tier`

### Filament — Loyalty Rules

- `list page renders`
- `create page renders`
- `can create a loyalty rule`
- `edit page renders`
- `can edit a loyalty rule`

### Feature — General

- `returns a successful response`
- `spa shell route returns 200 for public homepage`
- `spa shell route returns 200 for login page`
- `spa shell catch-all route returns 200 for unknown paths`
- `guests are redirected to the login page`
- `authenticated users can visit the dashboard`
- `registration requires gdpr consent`
- `registration with gdpr consent records timestamp`
- `registration with gdpr consent false is rejected`
- `guest can access doctors list without auth`
- `guest can access doctor slots without auth`
- `GET to /api/v1/contact returns 405 method not allowed`
- `POST /api/v1/contact with valid data sends mail and returns 200`
- `POST /api/v1/contact with missing fields returns 422`
- `POST /api/v1/contact rate limits after 5 requests from same IP`
- `GET /api/v1/services returns 200 with correct structure for a seeded service`
- `GET /api/v1/services is publicly accessible without authentication`
- `GET /api/v1/services returns an empty data array when no services exist`

### Feature — Patient Profile

- `GET profile returns correct fields for authenticated user`
- `PATCH profile updates name, phone, and notification_channel`
- `PATCH profile clears phone_verified_at when phone changes`
- `PATCH profile does not clear phone_verified_at when only name changes`
- `PATCH profile/password rejects wrong current_password with 422`
- `PATCH profile/password updates password when current_password is correct`
- `POST email/verification-notification sends notification when email is unverified`
- `POST email/verification-notification returns 200 when already verified without resending`
- `POST phone/send-otp returns 422 when phone is null`
- `POST phone/send-otp creates a PhoneVerification row and sends no real notification in testing env`
- `POST phone/verify-otp with valid code sets phone_verified_at and deletes OTP row`
- `POST phone/verify-otp with expired code returns 422`
- `POST phone/verify-otp with wrong code returns 422`
- `POST appointments returns 403 when email is unverified`
- `POST appointments returns 403 when phone is unverified`
- `POST appointments proceeds when both email and phone are verified`
- `POST appointments/request returns 403 when either verification is missing`
- `GET email verify redirects to /profile?verified=1 on success`
- `GET email verify redirects to /profile?error=invalid_link on bad hash`

### Feature — Loyalty

- `loyalty endpoint returns next_tier and points_to_next_tier when below the highest tier`
- `loyalty endpoint returns null next-tier fields when patient is at the highest tier`
- `loyalty endpoint returns null next-tier fields when no LoyaltyTier rows exist`
- `loyalty endpoint returns transactions array with correct shape`
- `loyalty endpoint returns service_name null for transactions without an appointment`
- `returns correct prices and points for a silver-tier patient`
- `returns discount_percent 0 and full original_price for standard tier`
- `returns points_to_earn 0 when no LoyaltyRule exists for service`
- `returns 401 when unauthenticated`
- `returns 422 when service_id is missing`
- `store() produces correct discount_pct and final_price after refactor`
- `LoyaltyPricingService::calculate returns correct result`
- `applies tier and promo discount multiplicatively`
- `does not apply promo discount when patient has no LoyaltyAccount`
- `does not apply promo discount from an expired rule`
- `does not apply promo discount when rule is_active = false`
- `unauthenticated request returns loyalty_discount_pct as null`
- `authenticated patient with Silver tier receives 5.0 discount`
- `authenticated patient with Gold tier receives 10.0 discount`
- `authenticated patient with Standard tier receives 0.0 discount`
- `authenticated patient with no LoyaltyAccount receives null`
- `service with active promo rule returns promo_discount_pct`
- `service with no active promo rule returns null for promo_discount_pct`
- `doctor services endpoint returns promo_discount_pct`

### Feature — Appointment Observer

- `awards points and creates a transaction when appointment is completed`
- `does not award points when no loyalty rule exists for the service`
- `does not create a duplicate transaction when already-completed appointment is re-saved`
- `does not award points when status changes to a non-Completed value`
- `creates a loyalty account via firstOrCreate if it does not exist when points are awarded`
- `upgrades tier to silver when balance crosses the silver threshold after earning`
- `does not change tier when balance does not cross the next threshold`
- `skips tier recalculation gracefully when the loyalty tiers table is empty`

### Feature — Notifications

- `does not send any mail when only notes change`
- `sends a reminder email to appointments starting in the 23-25h window`
- `stamps reminder_sent_at after sending a reminder`
- `skips appointments that already have reminder_sent_at set`
- `skips Pending and Cancelled appointments in the reminder window`
- `dispatches AppointmentBookedNotification after store()`
- `dispatches AppointmentRequestedNotification after requestStore()`
- `dispatches AppointmentConfirmedNotification when status changes to Confirmed`
- `dispatches AppointmentCompletedNotification when status changes to Completed`
- `dispatches AppointmentCancelledNotification when status changes to Cancelled`
- `dispatches AppointmentNoShowNotification when status changes to NoShow`
- `does not dispatch any notification when only notes change`
- `via() returns mail channel for notification_channel=email`
- `via() returns vonage channel for notification_channel=sms`
- `via() returns both channels for notification_channel=both`

### Feature — Rescheduling

- `reschedules a pending appointment and returns 200`
- `reschedules a confirmed appointment and returns 200`
- `returns 403 when a different patient tries to reschedule`
- `returns 422 when appointment has no slot`
- `returns 422 when appointment is cancelled`
- `returns 422 when appointment is completed`
- `returns 404 when new slot belongs to a different doctor`
- `returns 422 when new slot is already booked`
- `sets old slot is_booked to false after reschedule`
- `sets new slot is_booked to true after reschedule`
- `sends AppointmentRescheduledNotification to patient on success`
- `show returns 200 with appointment resource for the owning patient`
- `show returns 403 for a different patient`

### Feature — BackendFixesPass1

- `slot-based booking stores correct discount_pct and final_price for non-zero tier`
- `slot-based booking with standard tier stores discount_pct 0 and final_price equal to service price`
- `request-based booking stores discount_pct 0 and null final_price`
- `appointment resource response includes discount_pct and final_price keys`
- `no-show action sets appointment status to NoShow`
- `no-show action is not visible for pending appointments`
- `slot generation action sends warning notification when no slots are created`
- `confirm appointment request action populates final_price and discount_pct`

### Feature — Audit Log

- `creating a loyalty transaction writes an activity log entry`
- `updating loyalty account balance writes an activity log entry with old and new values`
- `updating loyalty account tier writes an activity log entry with old and new tier`
- `activity log list page renders for admin`

### Feature — Reports & Analytics

- `admin dashboard page renders without error`
- `appointments overview widget counts appointments in the current month via slot date`
- `appointments overview widget counts request-based appointments via preferred_date`
- `revenue estimate widget sums service prices for completed appointments in period`
- `revenue estimate widget shows zero when no completed appointments in period`

### Feature — Doctor Panel

- `doctor canAccessPanel returns true for the doctor panel`
- `patient canAccessPanel returns false for the doctor panel`
- `admin canAccessPanel returns false for the doctor panel`
- `doctor canAccessPanel returns false for the admin panel`
- `admin canAccessPanel returns true for the admin panel`
- `authenticated doctor is redirected into the doctor panel`
- `unauthenticated user is redirected to doctor login`
- `schedule list page loads for authenticated doctor`
- `scoped query only returns the authenticated doctors own schedule rows`
- `creating a schedule row forces doctor_id to the authenticated doctor`
- `edit page returns 404 for schedule belonging to another doctor`
- `create page loads for authenticated doctor`
- `appointments list page loads for authenticated doctor`
- `scoped query only returns the authenticated doctors own appointments`
- `doctor can save doctor_notes on their own appointment`
- `mark complete sets appointment status to completed`
- `mark no-show sets appointment status to no_show`
- `edit page returns 404 for appointment belonging to another doctor`

### Feature — Commands

- `command ensures all doctor-enum users have the spatie doctor role`
- `command skips users who already have the spatie doctor role`
- `command does not assign doctor role to patients`
- `command generates slots for active schedules`
- `command skips inactive schedules`
- `command respects --date and --days options`

### Feature — Slot Generation Service

- `generates slots for an active schedule in the date range`
- `service is idempotent - running twice produces no duplicates`
- `slot times match slot_duration_minutes`
- `dates not matching day_of_week produce no slots`

### Feature — Doctor Service Pivot

- `doctor_service table exists with expected columns`
- `GET /api/v1/doctors includes services array per doctor`
- `GET /api/v1/doctors includes empty services array for doctor with no services`
- `GET /api/v1/doctors/{doctor}/services returns only that doctors services`
- `GET /api/v1/doctors/{doctor}/services returns empty array for doctor with no services`
- `GET /api/v1/doctors/9999/services returns 404`

### Feature — User Resource (Admin)

- `list page renders`
- `create page renders`
- `can create a user and assigns spatie role`
- `creating a doctor user auto-creates a doctor profile`
- `edit form role change syncs spatie role and updates role column`
- `editing role to doctor creates doctor profile if missing`
- `reset password action updates the password hash`
- `verify email action sets email_verified_at`
- `revoke verifications action clears both verified-at columns`
- `cannot delete own user record`

### Feature — Debug (TempDebug)

- `debug access to /doctor root`
- `debug access to /doctor/doctor-appointments`

---

## 4. Key Technical Details

### Database Strategy

- **`RefreshDatabase`** is applied globally to the entire `Feature` suite via `tests/Pest.php`:
  ```php
  pest()->extend(TestCase::class)
      ->use(RefreshDatabase::class)
      ->in('Feature');
  ```
- Database driver: **SQLite in-memory** (`:memory:`) configured in `phpunit.xml`.

### Factories Used

| Factory | Used In |
|---|---|
| `User::factory()` | Almost every test file |
| `Appointment::factory()` | Appointment*, BackendFixes, EmailSms, Rescheduling |
| `Doctor::factory()` | DoctorApi, DoctorResource, EmailSms, DoctorServicePivot, BackendFixes |
| `Service::factory()` | ServicesPage, ServiceLoyalty, DoctorServicePivot, EmailSms, BackendFixes |
| `ScheduleSlot::factory()` | DoctorApi, EmailSms, Rescheduling, BackendFixes |
| `DoctorSchedule::factory()` | SlotGenerationService, GenerateSlotsCommand, DoctorScheduleResource |
| `LoyaltyTier::factory()` | LoyaltyBookingPreview, ServiceLoyalty, BackendFixes |
| `LoyaltyRule::factory()` | LoyaltyRuleResource, LoyaltyBookingPreview |

### Fakes & Mocking

| Fake | Files Using It |
|---|---|
| `Mail::fake()` | `AppointmentApiTest`, `AppointmentEmailNotifications`, `AppointmentObserver`, `BackendFixesPass1`, `ContactForm`, `LoyaltyStatus`, `ReportsAnalytics`, `Filament/AppointmentResource` |
| `Notification::fake()` | `Auth/PasswordReset`, `Auth/VerificationNotification`, `AppointmentRescheduling`, `EmailSmsNotifications`, `PatientProfile` |
| `Event::fake()` | `Auth/EmailVerification` |

### Authentication Strategies

- **`Sanctum::actingAs($user)`** — used in all API v1 tests where a patient token is required.
- **`$this->actingAs($user)`** — used for Filament/web session-based authentication (admin, doctor).
- **`Role::firstOrCreate()`** with Spatie Laravel Permission — bootstrapped in `beforeEach` across most tests requiring role-guarded routes.

### Artisan Command Testing

Tested via `$this->artisan(...)`:

| Command | Test File |
|---|---|
| `slots:generate` | `GenerateSlotsCommandTest` |
| `doctor:sync-roles` | `SyncDoctorRolesCommandTest` |

### Livewire Component Testing

`Livewire::test(ComponentClass::class)` is used throughout Filament resource tests and in `UserResourceTest`, `AuditLogTest`, `ReportsAnalyticsTest`, and `BackendFixesPass1Test`.
