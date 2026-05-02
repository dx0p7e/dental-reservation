## Context

After Change 20 (Full SPA UI Redesign), five bugs were confirmed through browser testing and static analysis. Three were reported in the change brief; two additional ones emerged from a systematic review of all SPA views, stores, and API controllers. None require architectural changes — all are narrow, targeted fixes to existing code. The bugs span both the Vue SPA (`resources/spa/`) and the Laravel API (`app/Http/Controllers/Api/V1/`).

## Goals / Non-Goals

**Goals:**
- Resolve all five confirmed bugs with minimal surgical changes
- Ensure the `logout()` flow leaves no ghost-authenticated state under any failure mode
- Ensure the booking flow works correctly whether entered via LandingView or from AppNavbar/AppointmentsView
- Make LoyaltyView resilient to both the "no account" and "account with no transactions" API responses

**Non-Goals:**
- Refactoring the booking flow architecture (multi-step wizard, dedicated store actions, etc.)
- Adding a proper loading state or skeleton to `AppointmentsView` cancel
- Changing Sanctum auth mode (session vs token)
- Any admin panel or Inertia layer changes

## Decisions

### Bug 1: Doctor not set in DoctorSlotsView

**Decision**: Fetch the doctor from `GET /api/v1/doctors/:id` inside `DoctorSlotsView.onMounted` and set `bookingStore.selectedDoctor` if not already set (i.e., if the user navigated directly rather than via LandingView).

**Alternatives considered**:
- *Remove the `selectedDoctor` guard from BookView*: Rejected. `doctor_id` is required by the booking API and the BookView summary row shows the doctor name — removing the guard would cause a broken UI and a 422 on the POST.
- *Store the doctor ID in the URL and fetch lazily in BookView*: Over-engineered for a three-step flow with no back-navigation requirements.

**Also**: `canConfirm()` in `DoctorSlotsView` is updated to check `bookingStore.selectedDoctor !== null` alongside slot and service, making the Continue button correctly disabled until the fetch completes.

### Bug 2: LoyaltyResource `transactions` missing from JSON

**Decision**: Fix on the backend — in `LoyaltyController`, call `$account->setRelation('transactions', collect([]))` on the fallback `LoyaltyAccount` instance so `whenLoaded` finds a loaded (empty) collection. Also make `transactions` optional in the TypeScript `LoyaltyAccount` interface and default to `[]` in the template access via `loyalty.transactions ?? []`.

**Alternatives considered**:
- *Fix only in the Vue template*: `loyalty.transactions?.length === 0` would stop the crash but the API would still send inconsistent shapes. Belt-and-suspenders is better here.
- *Change `whenLoaded` to `$this->transactions ?? []`*: This would silently hide an unloaded relation in production models, masking other potential bugs.

### Bug 3: Logout TransientToken + ghost auth state

**Decision**: Two changes in parallel:
1. **Backend** (`AuthController::logout`): Check `instanceof \Laravel\Sanctum\PersonalAccessToken` before calling `delete()`. Always call `Auth::guard('web')->logout()` and `session()->invalidate()` / `session()->regenerateToken()` afterward to cover both auth modes cleanly.
2. **Frontend** (`auth.ts`): Move `token.value = null; user.value = null; localStorage.removeItem('booking_token')` into a `finally` block so local state is always cleared regardless of whether the API call succeeds or throws.

**Alternatives considered**:
- *Catch the exception in Laravel and return 204 anyway*: The API should not swallow unexpected errors silently — explicit type-checking is cleaner and self-documenting.

### Bug 4: AppointmentsView cancel() error handling

**Decision**: Wrap `api.delete()` in try/catch, add a local `cancelError` ref, display an inline error message near the card. Refetch only on success.

### Bug 5: BookView hardcoded back link

**Decision**: Compute the back URL from `bookingStore.selectedDoctor?.id` with a fallback to `'/'`. Since `selectedDoctor` must be set for the user to reach BookView (after Bug 1 fix), the fallback case is only a defensive backstop.

## Risks / Trade-offs

- **`setRelation` on a new model instance** (Bug 2): `setRelation` is a public Eloquent method and works correctly on unsaved model instances. It sets the `relations` array, which `whenLoaded` inspects. Risk: none identified.
- **Always clearing local auth state on logout** (Bug 3): If the server fails to invalidate the token, the user's local state is cleared but their Sanctum token remains valid server-side until it expires or is purged. Acceptable trade-off — the alternative (keeping the user "logged in" after a 500) is worse.
- **Doctor API call on every DoctorSlotsView mount** (Bug 1): Adds one extra request (`GET /doctors/:id`) when the user navigates directly. Negligible performance impact for a patient-facing flow.

## Open Questions

None — all decisions are confirmed from source code analysis.
