## Context

The patient profile currently provides two verification mechanisms: email (link-click) and phone (Vonage OTP). Both must be completed before a patient can book an appointment. This change introduces a third, entirely optional mechanism — Smart-ID identity verification — which sits alongside these without replacing or altering them.

Smart-ID is an eIDAS-compliant mobile authenticator widely used in Estonia, Latvia, and Lithuania. SK ID Solutions (the operator) provides an official PHP SDK (`sk-id-solutions/smart-id-php-client`, v3.0.0, PHP 8.4+) and a publicly accessible demo environment that requires no commercial agreement. The demo Relying Party UUID is published and fixed (`00000000-0000-4000-8000-000000000000`).

The SPA communicates with the backend via a JSON API (Sanctum-authenticated). The existing phone OTP flow uses a two-step pattern (send → verify) which is the natural model to follow here.

Smart-ID sessions are asynchronous: after initiating a session the user approves on their mobile device. The backend must store interim session state and allow the frontend to poll for the result.

## Goals / Non-Goals

**Goals:**
- Patient can verify their identity via Smart-ID from the profile page
- No change to login, registration, or the booking gate
- Works fully in demo environment for development and demo purposes
- Personal identification number (asmens kodas) is never stored after the API call completes
- `smart_id_verified_at` timestamp stored on the user record on success
- Admin can see Smart-ID verification status in Filament

**Non-Goals:**
- Smart-ID as a login method
- Smart-ID affecting the booking gate (phone + email still required)
- QR code / device-link flow (notification-based flow is sufficient for profile verification)
- Production SK contract (demo environment is the target)
- Storing or using the personal identification number beyond the session

## Decisions

### 1. Notification-based flow (not device-link / QR)

**Decision:** Use the notification-based Smart-ID flow.

**Rationale:** The patient is already logged in and on the profile page (desktop or mobile). They enter their personal code once, a push notification is sent directly to their Smart-ID app, and they approve. No QR code to scan, no refreshing URLs every second. The UX is simpler and the server-side implementation is straightforward.

**Alternative considered:** Device-link QR flow — rejected because it requires refreshing the QR code every second (WebSocket or SSE complexity) and is designed for anonymous login scenarios, not verified-user profile actions.

### 2. Client-side polling (not server-side long-poll)

**Decision:** Frontend polls `GET /api/v1/smart-id/poll/{token}` every 2 seconds. Each poll makes a single status query to the SK API and returns immediately.

**Rationale:** PHP FPM workers are finite. A blocking long-poll (30s) waiting for the user to approve would hold a worker hostage. Short-lived poll requests (< 200ms each) are safe and the 2-second client interval is imperceptible to users.

**Alternative considered:** Server-sent events or WebSockets — overkill for this use case, adds infrastructure complexity.

### 3. Laravel Cache for session state (not database)

**Decision:** Store `{ sessionId, userId }` in Laravel's default cache with a 3-minute TTL, keyed by a cryptographically random polling token.

**Rationale:** 3 minutes matches SK's session lifetime exactly. The data is ephemeral — if it expires, the user simply tries again. The `userId` in the cache value prevents token-guessing attacks (a poll request is only honoured if the authenticated user's ID matches the cached userId).

**Alternative considered:** A `smart_id_sessions` database table — unnecessary persistence overhead for transient state that has a hard 3-minute lifetime.

### 4. Notification-based flow uses semantics identifier (PNOLT-xxxxx)

**Decision:** Accept `personal_code` and `country` from the frontend; construct a `SemanticsIdentifier` of type `PNO` (Personal Number).

**Rationale:** The personal code is what Lithuanian users know and use on all government forms. It's the standard identifier for notification-based Smart-ID flows in Lithuania.

**Data handling:** The personal code is passed directly to the SK API client and never written to any storage layer (no log, no cache, no database column).

### 5. OCSP revocation checking disabled in local/testing, enabled in production

**Decision:** Wrap OCSP configuration in an env flag (`SMARTID_OCSP_ENABLED`, default `false` in local).

**Rationale:** Demo OCSP requires manually uploading test certificates to `demo.sk.ee/upload_cert/`. Disabling OCSP in development avoids this setup step. Production should always have it on.

### 6. `smart_id_verified_at` is nullable timestamp on `users` table

**Decision:** Add a single nullable `smart_id_verified_at` timestamp column to `users`.

**Rationale:** Minimal footprint. The verification date is useful for audit (when did the clinic get identity proof?) and is displayable to admins. No personal code stored. No separate table needed.

## API Design

```
POST /api/v1/smart-id/initiate
  auth: sanctum
  body: { personal_code: string, country: "LT"|"EE"|"LV" }
  → 200 { verification_code: "1234", polling_token: "<uuid>" }
  → 422 validation errors
  → 429 throttle (max 3 attempts per 5 minutes per user)

GET  /api/v1/smart-id/poll/{token}
  auth: sanctum
  → 200 { status: "running" }
  → 200 { status: "ok" }       — sets smart_id_verified_at
  → 200 { status: "failed", reason: "refused"|"timeout"|"error" }
  → 404 token not found or expired
  → 403 token belongs to different user
```

## Frontend Flow (ProfileView.vue addition)

```
New section "Tapatybės patvirtinimas (Smart-ID)"
─────────────────────────────────────────────────
State A — already verified (smart_id_verified_at != null):
  ✓ Patvirtinta [date]

State B — not verified, form idle:
  Šalis: [LT ▼]
  Asmens kodas: [___________]
  [Patvirtinti tapatybę] button

State C — after initiate, polling:
  Smart-ID kodas: ████  (large, prominent number)
  "Atidarykite Smart-ID programėlę ir patvirtinkite"
  Spinner / progress indicator
  polling every 2s, max 90s then show timeout message

State D — success:
  flash → transitions to State A

State E — failed/refused:
  Error message + [Bandyti dar kartą] → back to State B
```

## Risks / Trade-offs

- **Personal code as user input** → Mitigation: only validated (11 digits for LT), passed straight to SK SDK, never stored or logged.
- **Demo OCSP not configured** → Mitigation: OCSP disabled by default in local via env flag; document that production needs it enabled.
- **SK demo downtime** → Mitigation: all Smart-ID errors caught and surfaced as user-friendly messages; feature is optional so downtime has no impact on core booking.
- **Cache eviction under load** → Low risk; if the polling token expires before the user approves (>3 min), they see a timeout and retry. No data loss.
- **Concurrent poll requests** → Two tabs polling simultaneously could both set `smart_id_verified_at`. Harmless — writing the same timestamp twice is idempotent; the `update()` call is not destructive.
- **Rate limiting** → 3 initiations per 5 minutes per user prevents abuse of the SK API.
