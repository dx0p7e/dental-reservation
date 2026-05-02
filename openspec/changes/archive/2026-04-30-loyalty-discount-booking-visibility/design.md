## Context

The booking flow is spread across three Vue views:
- `LandingView.vue` — shows a services table (informational, available to guests)
- `DoctorSlotsView.vue` — **service selection step**: patient picks a service + slot; calls `GET /api/v1/services` to populate the `<select>` dropdown
- `BookView.vue` — **confirmation step**: shows summary before POST to `/api/v1/appointments`

`GET /api/v1/services` is currently a **public, unauthenticated** route returning `ServiceResource::collection(Service::all())`. The resource emits `id, name, description, duration_minutes, price`.

`LoyaltyTier.discount_bonus_pct` is a `NOT NULL decimal(5,2)` column. Standard=0.00, Silver=5.00, Gold=10.00. A patient's tier comes from `LoyaltyAccount.tier`; a patient may have no `LoyaltyAccount` at all (new patient, never completed an appointment).

The `DoctorSlotsView.vue` and `BookView.vue` are only reached by authenticated patients (booking requires Sanctum auth at the POST step). In practice, the booking flow assumes the user is logged in, though no guard enforces it on the Vue-router routes.

## Goals / Non-Goals

**Goals:**
- Return `loyalty_discount_pct: float|null` on `GET /api/v1/services` for authenticated patients
- Show discount hint in the service `<select>` of `DoctorSlotsView.vue` when discount > 0
- Show discounted price on the `BookView.vue` confirmation summary when discount > 0
- Null-safe: guests and patients without a `LoyaltyAccount` receive `null`

**Non-Goals:**
- Storing the discounted price in the `appointments` record
- Guest-facing discount display
- Applying discounts in any financial calculation
- Admin panel visibility of per-booking discounts
- Changes to the `LoyaltyAccount` creation or tier upgrade logic

## Decisions

### 1 — Resolve the authenticated user inside `ServiceResource` via `auth('sanctum')->user()` (no middleware change)

**Decision:** Call `auth('sanctum')->user()` directly inside `ServiceResource::toArray()`. If it returns a user, compute the discount; if null, return `null` for `loyalty_discount_pct`.

**Rationale:** The `auth()` helper resolves the Sanctum guard at call time, which checks for a Bearer token or session cookie on any request — even on routes without the `auth:sanctum` middleware applied. This avoids breaking the public nature of the endpoint (guests still get a 200 with `loyalty_discount_pct: null`) without any middleware additions.

**Alternative considered:** Protect the route with `auth:sanctum`, and update `LandingView.vue` to handle 401. Rejected: it changes the public contract of the endpoint and breaks guest browsing of the services table on the landing page.

**Alternative considered:** Create a separate authenticated endpoint (`GET /api/v1/me/services`). Rejected: adds a route, a controller method, and requires the frontend to switch endpoints based on auth state. More complex for minimal benefit.

### 2 — `loyalty_discount_pct` semantics: `null` for unauthenticated/no account, `0.0` for standard tier

**Decision:** Return `null` when: (a) user is unauthenticated, or (b) user has no `LoyaltyAccount`. Return the numeric `discount_bonus_pct` value (including `0.0` for standard) when a `LoyaltyAccount` exists.

**Rationale:** `null` explicitly means "we have no discount data for this user". `0.0` means "you have an account but no discount at your current tier". The frontend treats both `null` and `0.0` as "don't show a badge" — the distinction is informational, not behavioural. Using the raw `discount_bonus_pct` value keeps the frontend free of tier-name coupling.

**Alternative considered:** Return `null` for both no-account and standard-tier. Simpler for the frontend, but loses the distinction. Kept as a valid fallback if the semantics cause confusion.

### 3 — Compute the discount in `ServiceResource` with a single sub-query, avoiding N+1

**Decision:** Load the authenticated user's `LoyaltyAccount.tier` once per request (outside the resource iteration), pass it into the resource via `ServiceResource::collection(...)->additional(...)` context or compute it in `ServiceController::index()` and pass the resolved `discount_bonus_pct` as a shared value.

**Preferred approach:** In `ServiceController::index()`, resolve the user's discount percentage once, then store it on the request attributes bag — the same `$request` object is passed into every `ServiceResource::toArray(Request $request)` call:
```php
$discountPct = null;
if ($user = auth('sanctum')->user()) {
    $tier = LoyaltyAccount::where('patient_id', $user->id)->value('tier');
    if ($tier !== null) {
        $discountPct = LoyaltyTier::where('tier', $tier)->value('discount_bonus_pct');
    }
}
$request->attributes->set('loyalty_discount_pct', $discountPct);
return ServiceResource::collection(Service::all());
```

Then in `ServiceResource::toArray(Request $request)`, read:
```php
'loyalty_discount_pct' => $request->attributes->get('loyalty_discount_pct'),
```

**Why not `->additional()`:** Calling `->additional(['discount_pct' => $discountPct])` on the collection adds the key to the top-level response wrapper alongside `data` — it is never injected per-item. Inside each resource's `toArray()`, `$this->additional` is always `[]`. The `$request->attributes` bag is the correct channel for sharing per-request context into resource items.

**Rationale:** Avoids hitting `LoyaltyAccount` and `LoyaltyTier` N times per service in the resource's `toArray` — the discount depends on the patient, not the service. One query pair regardless of service count.

**Alternative considered:** Computing entirely inside `ServiceResource::toArray()` via `auth('sanctum')->user()`. Works but makes the resource stateful and untestable in isolation; also triggers N queries if the guard re-resolves per-instance.

### 4 — Frontend: `Service` type extended; discount shown in `DoctorSlotsView.vue` select option and `BookView.vue` summary row

**Decision:**
- Add `loyalty_discount_pct: number | null` to the `Service` TypeScript type
- In `DoctorSlotsView.vue`, compute a label helper: `(service.loyalty_discount_pct ?? 0) > 0 ? " · ${service.loyalty_discount_pct}% member discount" : ""`; append to option text
- In `BookView.vue`, add a "Price" row to the summary block. When `selectedService.loyalty_discount_pct > 0`, show original price struck-through and computed discounted price: `price * (1 - discount_pct / 100)`

**Rationale:** Minimal-touch changes. No new components, no store changes beyond the type. The discount is presentational and does not mutate any state.

**Computed discounted price formula:** `Math.round(price * (1 - loyalty_discount_pct / 100) * 100) / 100` — rounds to 2 decimal places.

Note: `Service.price` is returned as a string from `ServiceResource` (`$this->price` on the Eloquent model). The frontend will need to call `parseFloat(service.price)` for arithmetic.

## Risks / Trade-offs

- **`auth('sanctum')->user()` on a public route may not resolve if Sanctum token detection is not bootstrapped on the API middleware group** → Mitigation: verify in tests that a request with a valid Bearer token returns the correct `loyalty_discount_pct`. If not resolving, wrap the route in an optional-auth middleware instead.
- **`price` is a string in the resource** → The frontend must parse it before arithmetic. Should be caught in TypeScript if the `Service` type is typed correctly.
- **N+1 guard if controller approach not used** → Mitigated by Decision 3: resolve in controller, not per-resource.
- **Stale discount if patient tier upgrades mid-session** → Acceptable; this is a read-through from the current tier at request time. No caching is added.

## Open Questions

*(none)*
