# Dental Reservation System — Full Project Context

> Generated: 2026-05-02. Intended as complete technical context for a bachelor thesis AI assistant.

---

## 1. Project Overview

This is **Danties Klinika** ("Dental Clinic"), a full-stack dental appointment reservation system built as a bachelor thesis project. The system is a modern SPA-driven web application for a Lithuanian dental clinic, covering patient self-service booking, doctor schedule management, an admin panel, a loyalty programme, email/SMS notifications, GDPR compliance, and a patient review system.

The system was built entirely using an AI-assisted development workflow called **OpenSpec** — every feature was proposed, designed, and implemented through a structured spec-driven process with AI assistance. There is no git history; the version of record is the OpenSpec archive of changes.

### Core Actors

| Role | Description |
|---|---|
| **Patient** | Self-registers, browses services and doctors, books/cancels/reschedules appointments, earns loyalty points, submits reviews |
| **Doctor** | Views their appointments, manages their own schedule, can mark appointments as completed or no-show via the doctor portal |
| **Admin** | Full system access via Filament admin panel — manages doctors, services, users, appointments, loyalty configuration, views audit logs and analytics |

---

## 2. Technology Stack

### Backend
| Package | Version | Purpose |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel Framework | v13 | Web framework |
| Laravel Sanctum | v4 | SPA authentication (cookie-based) |
| Laravel Fortify | v1 | Auth scaffolding (registration, verification) |
| Laravel Pail | v1 | Log tailing in dev |
| Laravel Pint | v1 | PHP code formatter (PSR-12 + opinionated) |
| Laravel Sail | v1 | Docker development environment |
| Filament | v4 | Admin panel + Doctor portal (two separate Filament panels) |
| Livewire | v3 | Underpins Filament |
| Spatie Laravel Permission | latest | Role-based access control (`patient`, `doctor`, `admin`) |
| Spatie Laravel Activitylog | latest | Audit log on models (Appointment, LoyaltyAccount, LoyaltyTransaction) |
| Vonage SMS | via laravel-notification-channels/vonage | SMS notification channel |
| Pest | v4 | Test framework |
| PHPUnit | v12 | Underlying test runner |

### Frontend
| Package | Version | Purpose |
|---|---|---|
| Vue | v3 | Patient SPA framework |
| Inertia Laravel | v3 | Server-driven SPA (used for doctor/admin portals) |
| @inertiajs/vue3 | v3 | Inertia Vue adapter |
| Vue Router | v4 (inside Vue SPA) | Client-side routing for patient SPA |
| Pinia | latest | State management (patient SPA) |
| vue-i18n | v9 | Internationalisation (Lithuanian primary, English fallback) |
| Tailwind CSS | v4 | Utility-first CSS framework |
| Vite | latest | Frontend build tool |
| Laravel Wayfinder | v0 | Generates TypeScript route/action functions from Laravel controllers |
| ESLint | v9 | JavaScript linting |
| Prettier | v3 | JavaScript formatting |

### Database
- **MySQL** (production)
- **SQLite in-memory** (tests via `RefreshDatabase`)

---

## 3. System Architecture

```
┌───────────────────────────────────────────────────────────────────┐
│                         Web Browser                                │
│                                                                     │
│  ┌─────────────────────────┐  ┌────────────┐  ┌────────────────┐  │
│  │   Patient SPA (Vue 3)   │  │ Admin Panel│  │ Doctor Portal  │  │
│  │  /  /about /doctors     │  │  /admin/*  │  │  /doctor/*     │  │
│  │  /book /dashboard/*     │  │ (Filament) │  │  (Filament)    │  │
│  │  /reviews /services     │  └────────────┘  └────────────────┘  │
│  └─────────────────────────┘                                       │
└───────────────────────────────────────────────────────────────────┘
              │                       │                │
              │ JSON API (Sanctum)    │ Filament       │ Filament
              │ Cookie auth           │ Admin Session  │ Doctor Session
              ▼                       ▼                ▼
┌───────────────────────────────────────────────────────────────────┐
│                     Laravel 13 Application                         │
│                                                                     │
│  ┌─────────────────┐  ┌────────────────────┐  ┌───────────────┐  │
│  │ API Controllers │  │  Filament Resources │  │  Observers    │  │
│  │ /api/v1/...     │  │  (Admin + Doctor)   │  │  Appointment  │  │
│  └─────────────────┘  └────────────────────┘  │  Doctor/User  │  │
│                                                └───────────────┘  │
│  ┌─────────────────────────────────────────────────────────────┐  │
│  │                     Eloquent Models                          │  │
│  │  User · Doctor · Service · ScheduleSlot · DoctorSchedule    │  │
│  │  Appointment · LoyaltyAccount · LoyaltyTier · LoyaltyRule   │  │
│  │  LoyaltyTransaction · PatientReview · Activity (log)        │  │
│  └─────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌─────────────────────┐  ┌──────────────────────────────────────┐│
│  │  Notifications      │  │  Services / Jobs                      ││
│  │  (email + SMS)      │  │  LoyaltyPricingService                ││
│  │  ShouldQueue        │  │  GenerateSlotsCommand                 ││
│  └─────────────────────┘  │  SendAppointmentReminders             ││
│                            └──────────────────────────────────────┘│
└───────────────────────────────────────────────────────────────────┘
              │
              ▼
         MySQL Database
```

### URL Separation

| Prefix | Served by | Auth method |
|---|---|---|
| `/` … `/dashboard/*` | Patient SPA (Blade shell + Vue) | Sanctum SPA cookie |
| `/admin/*` | Filament Admin Panel | Filament session |
| `/doctor/*` | Filament Doctor Portal | Filament session |
| `/api/auth/*` | Laravel Fortify-backed AuthController | Sanctum SPA cookie |
| `/api/v1/*` | JSON REST API | Sanctum SPA cookie |

---

## 4. Development Chronology

The project was built over 4 days (April 29 – May 2, 2026) across ~40 discrete changes:

### Day 1 — April 29: Foundation
1. Database migrations — all 10 core tables
2. Eloquent models — all models with relationships, casts, observers
3. Database seeders — roles (RoleSeeder), loyalty tiers (LoyaltyTierSeeder), admin user (AdminUserSeeder)
4. Filament admin panel — installation, `/admin` route, role gating
5. Auth API — Sanctum SPA cookie auth, register/login/logout/verify endpoints
6. Patient SPA — initial Vue 3 scaffold (later consolidated)
7. Slot generation — `slots:generate` Artisan command + daily scheduler
8. Booking API — `/api/v1/` REST endpoints for patients
9. SPA consolidation — moved from `patient-spa/` to `resources/spa/`, single Vite pipeline
10. Filament resources — DoctorResource, ServiceResource, LoyaltyTierResource, LoyaltyRuleResource
11. Appointment resource — AppointmentResource in Filament + PatientsRelationManager
12. Schedule Slots resource — DoctorScheduleResource in Filament
13. Patient loyalty status — extended LoyaltyResource with next_tier, points_to_next_tier, transactions

### Day 2 — April 30: UI Redesign + Backend Fixes
14. Full SPA UI redesign — design tokens, new Tailwind colour palette, all views rebuilt
15. Backend fixes (pass 1) — discount storage on appointments, NoShow Filament action, zero-slot feedback
16. Bug fixes — booking flow, loyalty crash, logout bug, missing error handling, broken back link
17. Loyalty discount booking visibility — service API shows discount_pct, booking flow shows discounted price
18. Reports & analytics — Filament dashboard with filterable widgets (appointments, loyalty distribution, revenue)
19. Audit log — LogsActivity on loyalty models + ActivityLogResource in Filament
20. Appointment email notifications — initial implementation (later superseded)

### Day 3 — May 1: Features + Polish
21. Full SPA UI redesign (second pass) — Inertia-based admin/doctor UI corrections
22. Email + SMS notifications — complete Notification architecture with 6 notification classes, Vonage SMS, queue support
23. Vue i18n (Lithuanian/English) — vue-i18n v9, all UI strings extracted to locale JSON files, LT/EN switcher in navbar
24. Services page + live landing — `/services` public page, LandingView pulls live API data
25. Public navbar restructure — `/dashboard/*` route namespace, public marketing pages (About, Contact, Loyalty)
26. GDPR consent gate — registration requires explicit consent checkbox, `gdpr_consent_at` stored
27. Admin user management — UserResource in Filament with full CRUD, role assignment, verification overrides
28. Appointment rescheduling — `PATCH /api/v1/appointments/{id}/reschedule`, RescheduleView.vue, AppointmentRescheduledNotification
29. Persistent booking draft — localStorage-backed draft, "Continue booking" navbar button
30. Doctor–service pivot — `doctor_service` table, scoped service API, admin assignment in Filament
31. Doctor portal (first attempt) — Inertia-based doctor portal (later replaced)
32. Doctor portal (Filament) — replaced Inertia approach with a second Filament panel at `/doctor`
33. Loyalty booking preview — `POST /api/v1/appointments/preview`, LoyaltyPricingService, rich price breakdown in BookView

### Day 4 — May 2: Demo Data + Final Features
34. Booking UI tweaks — three-column layout in DoctorSlotsView, sticky panels, day-grouped slots
35. Demo seeder — full realistic dataset (doctors, patients, services, appointments, loyalty history)
36. Loyalty rule promotions — `is_active` on loyalty_rules, promo discount stacked with tier discount
37. Doctor profile pictures — `photo_path` on doctors, FileUpload in Filament, photo_url in API, avatar in SPA
38. Patient reviews — `patient_reviews` table, API endpoints, ReviewsView, ReviewCard, ReviewForm, live landing testimonials
39. Doctor avatar on About page — DoctorAvatar component, AboutView updated
40. Auth-aware loyalty CTA — LoyaltyMarketingView shows different CTA for authenticated users
41. Seeder data fix — loyalty_seeder had inconsistent tier/points data; corrected

---

## 5. Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| name | varchar(255) | Full name |
| email | varchar(255) unique | |
| email_verified_at | timestamp nullable | Set on email verification |
| password | varchar(255) | bcrypt |
| phone | varchar(255) nullable | |
| phone_verified_at | timestamp nullable | Set on OTP verification |
| notification_channel | enum('email','sms','both') | Default: 'email' |
| role | enum('patient','doctor','admin') | Stored on users + Spatie roles |
| two_factor_secret | text nullable | Fortify 2FA |
| two_factor_recovery_codes | text nullable | |
| two_factor_confirmed_at | timestamp nullable | |
| remember_token | varchar(100) nullable | |
| gdpr_consent_at | timestamp nullable | Set on registration |
| deletion_requested_at | timestamp nullable | For GDPR deletion requests |
| created_at / updated_at | timestamp | |

### `doctors`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK(users) | One doctor per user |
| specialization | varchar(255) | e.g. "Odontologas" |
| bio | text nullable | |
| photo_path | varchar(255) nullable | Relative path in storage/public/doctors/ |
| is_active | tinyint(1) default 1 | |
| created_at / updated_at | timestamp | |

### `services`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| description | text nullable | |
| duration_minutes | smallint unsigned | |
| price | decimal(10,2) | Base price in EUR |
| complexity | enum('simple','complex') | |
| created_at / updated_at | timestamp | |

### `doctor_service` (pivot)
| Column | Type | Notes |
|---|---|---|
| doctor_id | bigint unsigned FK(doctors) | Composite PK |
| service_id | bigint unsigned FK(services) | Composite PK |

### `doctor_schedules`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| doctor_id | bigint unsigned FK(doctors) | |
| day_of_week | tinyint unsigned | 1=Mon … 7=Sun |
| start_time | time | |
| end_time | time | |
| slot_duration_minutes | smallint unsigned | How to subdivide the window |
| is_active | tinyint(1) default 1 | |
| created_at / updated_at | timestamp | |

### `schedule_slots`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| doctor_id | bigint unsigned FK(doctors) | Denormalized from schedule |
| date | date | Concrete booking date |
| start_time | time | |
| end_time | time | |
| slot_type | enum('self','request') | self=direct booking, request=admin assigns |
| is_booked | tinyint(1) default 0 | Locked atomically on booking |
| created_at / updated_at | timestamp | |

### `appointments`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| patient_id | bigint unsigned FK(users) | |
| doctor_id | bigint unsigned FK(doctors) | |
| service_id | bigint unsigned FK(services) | |
| slot_id | bigint unsigned FK(schedule_slots) nullable | Null for request-based bookings |
| preferred_date | date nullable | For request-based bookings |
| status | enum('pending','confirmed','cancelled','completed','no_show') | |
| discount_pct | decimal(5,2) default 0.00 | Loyalty tier discount applied at booking |
| final_price | decimal(8,2) nullable | Computed at booking; null for requests |
| notes | text nullable | Patient-facing note |
| doctor_notes | text nullable | Internal note |
| reminder_sent_at | timestamp nullable | Guards against duplicate reminders |
| created_at / updated_at | timestamp | |

Note: `Appointment` model uses `LogsActivity` from spatie/laravel-activitylog — every status change is logged to `activity_log`.

### `loyalty_tiers`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| tier | enum('standard','silver','gold') unique | |
| points_threshold | int unsigned | Points needed to reach this tier |
| discount_bonus_pct | decimal(5,2) | % discount applied to bookings |

Seeded values:
- standard: threshold=0, discount=0%
- silver: threshold=500, discount=5%
- gold: threshold=1500, discount=10%

### `loyalty_accounts`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| patient_id | bigint unsigned FK(users) unique | One per patient |
| points_balance | int unsigned | Current balance |
| tier | enum('standard','silver','gold') | Denormalized; updated by AppointmentObserver |

Uses `LogsActivity` tracking `points_balance` and `tier` fields.

### `loyalty_rules`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| service_id | bigint unsigned FK(services) unique | One rule per service |
| points_earned | int unsigned | Points awarded when this service is completed |
| discount_pct | decimal(5,2) | Promo discount percentage |
| valid_months | tinyint unsigned nullable | How many months the promo is valid (null=forever) |
| is_active | tinyint(1) default 1 | Manual on/off toggle |

### `loyalty_transactions`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| loyalty_account_id | bigint unsigned FK(loyalty_accounts) | |
| appointment_id | bigint unsigned FK(appointments) nullable | |
| points_delta | int | Positive=earn, negative=redeem |
| type | enum('earn','redeem') | |
| created_at | timestamp | |

Uses `LogsActivity` tracking `points_delta` and `type`.

### `patient_reviews`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| patient_id | bigint unsigned FK(users) unique | One review per patient |
| rating | tinyint unsigned | 1–5 stars |
| title | varchar(150) | |
| body | text | |
| is_published | tinyint(1) default 1 | |
| created_at / updated_at | timestamp | |

### `activity_log` (spatie/laravel-activitylog)
Polymorphic audit log. Tracks: subject (what changed), causer (who did it), event, attribute_changes (old/new values), properties.

### `phone_verifications`
Stores OTP codes for phone number verification (6-digit, with expiry).

### `passkeys`
WebAuthn/passkey credentials (via spatie/passkeys or similar).

### `jobs`, `failed_jobs`, `job_batches`
Laravel queue tables (database driver). Used for queued notifications.

### Spatie Permission tables
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — standard spatie/laravel-permission schema.

---

## 6. Authentication & Authorization

### Two Auth Surfaces

**Patient/API auth (Sanctum SPA cookie mode)**
- `POST /api/auth/register` — patient self-registration; assigns `patient` role; records `gdpr_consent_at`; requires `gdpr_consent: true` field
- `POST /api/auth/login` — issues `HttpOnly` session cookie (Sanctum SPA mode; no token in localStorage)
- `POST /api/auth/logout` — invalidates session
- `GET /api/auth/user` — returns authenticated user + role
- `GET /api/auth/email/verify/{id}/{hash}` — email verification (signed URL)
- `POST /api/auth/email/resend` — resend verification email
- `POST /api/v1/phone/send-otp` — send phone OTP
- `POST /api/v1/phone/verify-otp` — verify phone OTP

**Admin/Doctor auth (Filament built-in session)**
- `/admin/login` — Filament admin login; only users with `admin` role can access `/admin`
- `/doctor/login` — Filament doctor login; only users with `doctor` role can access `/doctor`

### Role System

Roles are stored in two places (dual-write for compatibility):
1. `users.role` enum column — primary source
2. `model_has_roles` (Spatie) — required by middleware and gates

A `UserObserver` syncs the Spatie role automatically when a user is created. A `DoctorObserver` assigns the `doctor` Spatie role when a Doctor profile is created. A `SyncDoctorRoles` Artisan command reconciles any drift.

### Policies & Authorization

- `AppointmentPolicy` — patients can only view/cancel/reschedule their own appointments
- `DoctorSchedulePolicy` — doctors can only manage their own schedule rows
- Filament panels gate access with `canAccessPanel()` on the `User` model, branching by panel ID

---

## 7. API Reference (Patient-Facing, `/api/v1/`)

All endpoints under `/api/v1/` follow REST conventions and return JSON. Pagination uses Laravel's standard cursor/page pagination. Resources are Eloquent API Resources.

### Auth
| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/auth/register` | — | Patient registration (legacy token endpoint) |
| POST | `/api/v1/auth/login` | — | Patient login (legacy token) |
| POST | `/api/v1/auth/logout` | sanctum | Logout |

### Doctors
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/doctors` | — | List active doctors with services array |
| GET | `/api/v1/doctors/{doctor}` | — | Single doctor |
| GET | `/api/v1/doctors/{doctor}/slots` | — | Available (non-booked) slots, filterable by `?date=` |
| GET | `/api/v1/doctors/{doctor}/services` | — | Services this doctor provides |

### Services
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/services` | — (optional auth) | All services; adds `loyalty_discount_pct` and `promo_discount_pct` for authenticated patients |

### Appointments
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/appointments` | sanctum | Patient's own appointments |
| POST | `/api/v1/appointments` | sanctum | Create booking (with slot — atomic lock) |
| POST | `/api/v1/appointments/preview` | sanctum | Pricing preview without creating |
| POST | `/api/v1/appointments/request` | sanctum | Request-based booking (no slot, preferred_date) |
| GET | `/api/v1/appointments/{appointment}` | sanctum | Single appointment |
| DELETE | `/api/v1/appointments/{appointment}` | sanctum | Cancel (pending/confirmed only) |
| PATCH | `/api/v1/appointments/{appointment}/reschedule` | sanctum | Reschedule to new slot |

### Loyalty
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/loyalty` | sanctum | Points balance, tier, next_tier, points_to_next_tier, transaction history |

### Profile
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/profile` | sanctum | Current user profile |
| PATCH | `/api/v1/profile` | sanctum | Update name, email, phone |
| PATCH | `/api/v1/profile/password` | sanctum | Change password |

### Reviews
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/reviews` | — | Published reviews (latest first, limit 50) |
| POST | `/api/v1/reviews` | sanctum | Submit review (one per patient, 409 on duplicate) |

### Contact
| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/contact` | — | Rate-limited contact form; sends `ContactFormMail` |

### Phone Verification
| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/phone/send-otp` | sanctum | Send OTP to phone |
| POST | `/api/v1/phone/verify-otp` | sanctum | Verify OTP |

---

## 8. Loyalty System

The loyalty system is a full points-and-tiers mechanism that incentivises repeat bookings.

### Tier Progression
```
Standard (0 pts) → Silver (500 pts) → Gold (1500 pts)
     0% discount        5% discount       10% discount
```

### How Points Are Earned
When an appointment is marked `Completed` by a doctor or admin:
1. `AppointmentObserver::handleCompleted()` runs inside a DB transaction
2. Looks up `LoyaltyRule` for the appointment's service
3. Creates a `LoyaltyTransaction` (type='earn', points_delta=rule.points_earned)
4. Increments `loyalty_account.points_balance`
5. Queries `loyalty_tiers` to find the best qualifying tier (`points_threshold <= balance`)
6. If tier changed, updates `loyalty_account.tier`

### Promo Discounts (LoyaltyRule.discount_pct)
Service-level promotional discounts are stacked multiplicatively on top of tier discounts via `LoyaltyPricingService`:
```
final_price = base_price × (1 - tier_discount) × (1 - promo_discount)
```
A rule is active when `is_active=true` AND either `valid_months IS NULL` or the rule hasn't expired.

### Booking Preview (`POST /api/v1/appointments/preview`)
Before confirming a booking, the frontend calls the preview endpoint to get an authoritative server-side price breakdown:
- `original_price` — service base price
- `tier_discount_pct` — patient's tier discount
- `promo_discount_pct` — active rule discount (if any)
- `discount_amount` — total discount in EUR
- `final_price` — what the patient will pay
- `points_to_earn` — points they'll receive on completion
- `current_balance` — current points balance
- `current_tier` — current tier name

### API Response (GET /api/v1/loyalty)
```json
{
  "points_balance": 650,
  "tier": "silver",
  "next_tier": "gold",
  "points_to_next_tier": 850,
  "transactions": [
    {
      "id": 1,
      "type": "earn",
      "points_delta": 75,
      "service_name": "Dantų valymas",
      "created_at": "2026-04-15"
    }
  ]
}
```

---

## 9. Notification System

Built on Laravel's `Notification` architecture (not `Mailable`). All notifications implement `ShouldQueue` (database driver).

### Six Appointment Notifications

| Class | Trigger |
|---|---|
| `AppointmentBookedNotification` | Patient self-books a slot |
| `AppointmentRequestedNotification` | Patient submits a date-request |
| `AppointmentConfirmedNotification` | Status transitions to `Confirmed` |
| `AppointmentCompletedNotification` | Status transitions to `Completed` |
| `AppointmentCancelledNotification` | Status transitions to `Cancelled` |
| `AppointmentNoShowNotification` | Status transitions to `NoShow` |
| `AppointmentRescheduledNotification` | Patient reschedules to a new slot |

### Channels
Each user has a `notification_channel` preference (`email`, `sms`, `both`). The notification's `via()` method reads this:
```php
match($notifiable->notification_channel) {
    'sms'  => ['vonage'],
    'both' => ['mail', 'vonage'],
    default => ['mail'],
}
```

### Email Templates
Blade markdown templates in `resources/views/notifications/appointments/`. Each contains: clinic header, event body, appointment summary (service, doctor, date/time, price if applicable), contact footer.

### SMS
Via Vonage (laravel-notification-channels/vonage). Short Lithuanian-language strings ≤ 160 characters.

### Reminder Command
`php artisan appointments:send-reminders` — runs hourly (scheduled in `routes/console.php`). Queries `Confirmed` appointments in the 23–25h window, sends reminder, stamps `reminder_sent_at`.

---

## 10. Admin Panel (Filament, `/admin`)

Full CRUD admin interface. Access restricted to users with `admin` Spatie role.

### Resources

| Resource | Model | Features |
|---|---|---|
| `DoctorResource` | Doctor | CRUD; BelongsTo user; service multi-select (pivot); profile photo upload; PatientsRelationManager |
| `ServiceResource` | Service | CRUD; complexity enum |
| `LoyaltyTierResource` | LoyaltyTier | CRUD; tier/threshold/discount fields |
| `LoyaltyRuleResource` | LoyaltyRule | CRUD; per-service points/discount/validity; is_active toggle |
| `AppointmentResource` | Appointment | CRUD; status badge; NoShow inline action; note both patient and doctor fields |
| `DoctorScheduleResource` | DoctorSchedule | CRUD; day-of-week; overlap validation; "Generate Slots" action with zero-slot warning |
| `UserResource` | User | Full CRUD; role assignment; email/phone verification overrides; password reset; loyalty + appointments relation managers; self-deletion guard |
| `ActivityLogResource` | Activity (Spatie) | Read-only; filterable by event type, subject model |

### Dashboard (custom Filament page)
Filterable by date range (This Month / Last Month / Last 30 Days / All Time).

**Widgets:**
- `AppointmentsOverviewWidget` — total and per-status breakdown
- `LoyaltyDistributionWidget` — patient counts per tier
- `RevenueEstimateWidget` — sum of `services.price` for Completed appointments

---

## 11. Doctor Portal (Filament, `/doctor`)

A second Filament panel, separate from the admin panel. Doctors log in at `/doctor` using the same credentials as the API (the `users` table).

### Resources

| Resource | Features |
|---|---|
| `DoctorAppointmentResource` | Doctors see ONLY their own appointments; can add `doctor_notes`; "Mark Completed" and "Mark No-Show" actions |
| `DoctorScheduleResource` | Doctors can create/edit/delete ONLY their own `DoctorSchedule` rows; `doctor_id` is always forced to the authenticated doctor |

---

## 12. Patient SPA (Vue 3)

Lives in `resources/spa/`. Served from the Laravel Blade shell (`resources/views/spa.blade.php`). All public routes fall through to the SPA catch-all in `routes/web.php`.

### Architecture
- **Entry point**: `resources/spa/main.ts`
- **Router**: Vue Router 4 (`createWebHistory`); auth guard redirects `/dashboard/*` to `/login` if unauthenticated
- **State**: Pinia stores (`authStore`, `bookingStore`)
- **HTTP**: Built-in fetch (Inertia v3 removed Axios); Sanctum SPA cookie mode (no tokens)
- **i18n**: vue-i18n v9, default locale `lt` (Lithuanian), fallback `en`; locale persisted to `localStorage`
- **TypeScript**: Full typing; `resources/spa/types/index.ts` defines all API response interfaces
- **Wayfinder**: TypeScript route helpers generated from Laravel controllers into `@/actions/` and `@/routes/`

### SPA Pages

**Public (no auth required):**
| Path | Component | Description |
|---|---|---|
| `/` | `LandingView.vue` | Hero, live services (first 6 from API), live doctor cards with avatars, live reviews (top 3), how-it-works, CTA |
| `/about` | `AboutView.vue` | Static clinic info, doctor cards with circular photo/initials avatars |
| `/services` | `ServicesView.vue` | Full services listing from API with loading/empty/error states |
| `/doctors` | `DoctorsView.vue` | Doctor cards with service badges; auth-gated "View Slots" button |
| `/doctors/:id/slots` | `DoctorSlotsView.vue` | 3-column layout: service select + date filter | day-grouped slot grid | sticky confirm panel; loyalty discount badges |
| `/loyalty` | `LoyaltyMarketingView.vue` | Tier benefits marketing page; auth-aware CTA (dashboard link vs register) |
| `/reviews` | `ReviewsView.vue` | All published reviews in grid; auth-gated "Write a review" button with ReviewForm modal |
| `/contact` | `ContactView.vue` | Contact form → `POST /api/v1/contact` |
| `/privacy` | `PrivacyView.vue` | Privacy policy stub |
| `/login` | `LoginView.vue` | Login form |
| `/register` | `RegisterView.vue` | Registration form with GDPR consent checkbox |

**Authenticated (requires auth, under `/dashboard/`):**
| Path | Component | Description |
|---|---|---|
| `/dashboard/appointments` | `AppointmentsView.vue` | Card list with status badges, cancel + reschedule buttons, price display |
| `/dashboard/appointments/:id/reschedule` | `RescheduleView.vue` | Slot picker for rescheduling; fetches doctor's available slots |
| `/dashboard/book` | `BookView.vue` | Booking confirmation with server-authoritative price breakdown from `/preview`; confirm button |
| `/dashboard/loyalty` | `LoyaltyDashboardView.vue` | Points balance, tier, progress bar to next tier, transaction history |
| `/dashboard/profile` | `ProfileView.vue` | Edit name/email/phone, change password |
| `/dashboard/request-appointment` | `RequestBookingView.vue` | Request-based booking (no slot picker, just preferred date + service) |

### Shared Components
- `AppNavbar.vue` — sticky navbar; public links + auth dropdown + "Continue booking" draft pill + LT/EN switcher
- `DoctorAvatar.vue` — circular avatar with photo or initials fallback
- `PageHeader.vue` — reusable page header
- `StatusBadge.vue` — appointment status colour badges
- `ReviewCard.vue` — displays a single review (stars, title, body, patient name, date)
- `ReviewForm.vue` — modal for submitting a new review (star picker, title, body)

### Design System
Custom Tailwind CSS v4 colour tokens defined in `resources/css/app.css` as `@theme inline {}`:
- `clinic-dark` — near-black for headings and navbar
- `clinic-blue` — primary action colour
- `clinic-teal` — loyalty/accent colour
- `clinic-surface` — card background
- `clinic-border` — subtle borders
- `clinic-text` — body text
- `clinic-muted` — secondary/hint text

### Persistent Booking Draft
The booking store (`bookingStore`) persists `selectedDoctor`, `selectedSlot`, `selectedService` to `localStorage` (`booking_draft` key). Stale drafts (slot datetime in the past) are discarded on rehydration. When an authenticated patient has an active draft, a teal "Continue booking" pill appears in the navbar.

---

## 13. Slot Generation

Doctor availability is a two-level system:

```
DoctorSchedule (template)          ScheduleSlot (concrete)
────────────────────────────       ──────────────────────────────
day_of_week: 1 (Monday)     ──▶   date: 2026-05-05
start_time: 09:00                  start_time: 09:00
end_time: 17:00                    end_time: 09:30
slot_duration_minutes: 30          is_booked: false
is_active: true
```

**`php artisan slots:generate`** reads active `DoctorSchedule` records and creates `ScheduleSlot` rows for a date range using `firstOrCreate`. Runs daily (scheduled in `routes/console.php`) generating 14 days ahead. Also triggerable on-demand via a Filament "Generate Slots" action on `DoctorScheduleResource`.

---

## 14. Booking Flow

### Self-booking (slot assigned immediately)
```
Patient                           Laravel
  │                                  │
  ├── GET /api/v1/doctors ──────────▶│ Returns doctors with services
  ├── GET /api/v1/doctors/1/slots ──▶│ Returns non-booked slots
  ├── POST /api/v1/appointments ─────│
  │   preview ──────────────────────▶│ LoyaltyPricingService.calculate()
  │                                  │ Returns price breakdown
  ├── Confirm ──────────────────────▶│
  │   POST /api/v1/appointments      │ DB transaction:
  │                                  │   lockForUpdate(slot)
  │                                  │   slot.is_booked = true
  │                                  │   appointment created
  │                                  │   discount_pct + final_price stored
  │                                  │ Dispatch AppointmentBookedNotification
  │◀── 201 Created ─────────────────│
```

### Request-based booking (no slot yet)
```
POST /api/v1/appointments/request
Body: { doctor_id, service_id, preferred_date }
→ Creates appointment with slot_id=null, status=pending
→ Dispatch AppointmentRequestedNotification
→ Admin assigns slot via Filament → status→confirmed → AppointmentConfirmedNotification
```

### Rescheduling
```
PATCH /api/v1/appointments/{id}/reschedule
Body: { slot_id }
→ Policy: patient owns appointment
→ Must be pending or confirmed; must have slot_id
→ New slot must belong to same doctor; must not be booked
→ DB transaction: free old slot, take new slot
→ Dispatch AppointmentRescheduledNotification
```

---

## 15. GDPR Compliance

- Registration requires explicit `gdpr_consent: true` field (422 without it)
- `users.gdpr_consent_at` timestamp stored on registration
- `users.deletion_requested_at` column exists for future GDPR erasure flow
- `PrivacyView.vue` at `/privacy` (stub page)
- All admin access to patient data is logged in `activity_log` via `LogsActivity`

---

## 16. Demo Seed Data

The `DemoSeeder` (run via `php artisan db:seed --class=DemoSeeder`) creates:

**Services (8):**
Dantų apžiūra, Dantų valymas, Dantų plombavimas, Šaknų kanalų gydymas, Dantų ekstrakcija, Dantenų gydymas, Dantų balinimas, Implantų klinikinis patikrinimas — with real Lithuanian descriptions, prices (30–200 EUR), and durations.

**Doctors (3):**
- Dr. Marta Kazlauskienė (user: marta.k@example.lt) — Bendrosios odontologijos specialistė
- Dr. Tomas Paulauskas (user: tomas.p@example.lt) — Ortodontas
- Dr. Aistė Rimkutė (user: aiste.r@example.lt) — Periodontologė

**Patients (4):**
- Jonas Stankevičius (jonas.s@example.lt) — silver tier, 650 pts
- Eglė Mackevičiūtė (egle.m@example.lt) — standard tier, 45 pts
- Rūta Jankauskaitė (ruta.j@example.lt) — gold tier, 1650 pts
- Andrius Butkus (andrius.b@example.lt) — standard tier, 15 pts

**Appointments:** 2–4 per patient spanning past (completed, no_show) and future (confirmed, pending) statuses.

**Loyalty accounts** consistent with tier thresholds (standard <500pts, silver 500–1499pts, gold ≥1500pts).

**Reviews (4):** Lithuanian-language reviews from Jonas, Eglė, Rūta, Andrius.

---

## 17. Testing

Tests use **Pest v4**. Database: SQLite in-memory with `RefreshDatabase` trait.

### Test structure
```
tests/
  Pest.php          — global uses (RefreshDatabase, etc.)
  TestCase.php      — base test case
  Feature/          — HTTP feature tests (API endpoints, Filament resources)
  Unit/             — Unit tests (commands, services, value objects)
```

### Coverage approach
- Every API endpoint has a Feature test covering: happy path, auth-guarded (401), validation (422), policy enforcement (403/404)
- Every Filament Resource has tests covering: list renders, create renders, edit renders, can create, can edit
- Appointment lifecycle tests: booking atomicity, cancellation, rescheduling, no-show
- Loyalty system tests: points awarded on completion, tier upgrade, pricing calculation
- Command tests: `slots:generate`, `appointments:send-reminders`, `sync-doctor-roles`

### Running tests
```bash
php artisan test --compact                      # all tests
php artisan test --compact --filter=Loyalty     # filtered
php artisan test --compact tests/Feature/Api/   # specific directory
```

---

## 18. Key Architectural Decisions

### 1. Sanctum SPA Cookie Mode (not Bearer Tokens)
The patient SPA uses HttpOnly session cookies via Sanctum SPA mode rather than localStorage tokens. This eliminates XSS token theft risk. The SPA is served from the same Laravel origin, so same-site cookies work correctly.

### 2. Dual Filament Panels
Rather than building a custom Inertia-based doctor portal (which was attempted and reverted), the final architecture uses two separate Filament panels (`admin` and `doctor`). This reuses Filament's authentication, navigation, and resource infrastructure without a custom frontend.

### 3. LoyaltyPricingService
Pricing logic was extracted from `AppointmentController::store()` into a `LoyaltyPricingService` class to avoid duplication between the create and preview endpoints. The service returns a `LoyaltyPriceResult` value object.

### 4. Tier Denormalization
`loyalty_accounts.tier` is a denormalized cache of what's computable from `points_balance + loyalty_tiers`. The `AppointmentObserver` keeps it in sync on every completion event. `LoyaltyResource` uses the stored `tier` column to anchor the `next_tier` lookup.

### 5. Observer-Driven Side Effects
The `AppointmentObserver::handleCompleted()` runs the full loyalty award flow inside a `DB::transaction()`. This ensures points + tier + transaction are always written atomically with `lockForUpdate()` on the slot.

### 6. Doctor-Service Pivot
Doctors don't offer all services. The `doctor_service` pivot restricts service selection in the booking flow to only services a given doctor actually provides. This is enforced at the API level via `GET /api/v1/doctors/{doctor}/services`.

### 7. OpenSpec Development Workflow
Every feature was built through a structured AI-assisted workflow:
1. **Propose** — write a `proposal.md` describing what and why
2. **Design** — write a `design.md` with data shapes, component design, API contracts
3. **Tasks** — generate a `tasks.md` checklist
4. **Implement** — AI executes tasks one by one
5. **Archive** — completed change moves to `openspec/changes/archive/`

This workflow means the OpenSpec archive in `openspec/changes/archive/` is the complete feature history of the project.

---

## 19. Project File Structure (Key Directories)

```
app/
  Actions/Fortify/        — Fortify action overrides (CreateNewUser, etc.)
  Console/Commands/       — GenerateSlotsCommand, SendAppointmentReminders, SyncDoctorRoles
  Enums/                  — AppointmentStatus, etc.
  Filament/
    Admin/Resources/      — All admin panel resources
    Doctor/Resources/     — Doctor portal resources
    Pages/Dashboard.php   — Custom filterable dashboard
    Widgets/              — AppointmentsOverview, LoyaltyDistribution, RevenueEstimate
  Http/
    Controllers/
      Api/V1/             — All patient-facing API controllers
    Requests/Api/V1/      — Form request validation classes
    Resources/Api/V1/     — Eloquent API Resources
  Mail/                   — ContactFormMail
  Models/                 — All Eloquent models
  Notifications/          — 7 appointment notification classes
  Observers/              — AppointmentObserver, UserObserver, DoctorObserver
  Policies/               — AppointmentPolicy, DoctorSchedulePolicy
  Providers/              — AppServiceProvider, FortifyServiceProvider
  Services/               — LoyaltyPricingService, LoyaltyPriceResult

database/
  migrations/             — All database migrations
  seeders/                — DatabaseSeeder, DemoSeeder, and all sub-seeders
  factories/              — Model factories

resources/
  css/app.css             — Tailwind v4 @theme tokens + @source directives
  spa/
    components/           — Reusable Vue components
    locales/en.json       — English UI strings
    locales/lt.json       — Lithuanian UI strings (primary)
    main.ts               — SPA entry point
    plugins/i18n.ts       — vue-i18n setup
    router/index.ts       — Vue Router with auth guard
    stores/               — Pinia stores (auth, booking)
    types/index.ts        — TypeScript interfaces for all API types
    views/                — All Vue page components
  views/
    mail/                 — (legacy) Mailable Blade templates
    notifications/        — Notification Blade markdown templates
    spa.blade.php         — SPA shell (Vite entry point injection)

routes/
  api.php                 — All API routes
  web.php                 — SPA catch-all + Inertia/admin routes
  console.php             — Scheduled commands
  settings.php            — Settings page routes

openspec/
  changes/archive/        — Complete history of all implemented changes
  specs/                  — Persistent capability specifications
  config.yaml             — OpenSpec configuration
```

---

## 20. Environment Variables (key `.env` entries)

```ini
APP_NAME="Danties Klinika"
APP_URL=http://localhost
DB_CONNECTION=mysql

# Queue (for notifications)
QUEUE_CONNECTION=database

# Vonage SMS
VONAGE_KEY=
VONAGE_SECRET=
VONAGE_SMS_FROM=

# Storage
FILESYSTEM_DISK=public

# Admin seeding
ADMIN_EMAIL=admin@klinika.lt
ADMIN_PASSWORD=secret
```

---

*End of context document. All 40 changes are reflected above.*
