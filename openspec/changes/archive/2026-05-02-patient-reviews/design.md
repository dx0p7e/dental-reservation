## Context

The landing page has a hardcoded `testimonials` array in `LandingView.vue` with fake Lithuanian-sounding names and quotes. The SPA has no reviews concept in the backend or in any model. Patients cannot submit real reviews.

The change adds a `patient_reviews` table, a `PatientReview` model, a `ReviewController` with index and store actions, and a new `/reviews` SPA page. The landing page testimonials section is rewritten to fetch live data.

## Goals / Non-Goals

**Goals:**
- Patients (authenticated) can submit exactly one review with a star rating (1–5), an optional title, and a body text.
- Any visitor can read published reviews via `GET /api/v1/reviews`.
- Landing page shows the 3 most recent reviews from the API with a "view all" link.
- New `/reviews` SPA page displays all reviews in a grid with a "Add review" button (visible only when authenticated and review not yet submitted).
- Realistic Lithuanian seed data via `ReviewSeeder`.

**Non-Goals:**
- Admin moderation / review approval workflow (all submitted reviews are immediately visible).
- Editing or deleting a review after submission.
- Replies or nested comments.
- Pagination beyond a simple "load more" — initial implementation returns all reviews (capped at 50).

## Decisions

### D1 — One review per patient (unique constraint)
**Decision:** Enforce uniqueness at the DB level (`UNIQUE(patient_id)`) on `patient_reviews`.  
**Rationale:** Prevents duplicate submissions without application-layer race conditions. The store endpoint returns HTTP 409 if a review already exists.  
**Alternative considered:** Allow multiple reviews, show only most recent — rejected as it complicates the UI and seeding.

### D2 — No moderation queue
**Decision:** Reviews are visible immediately on submission (`is_published` column defaulting to `true`).  
**Rationale:** Keeps the implementation minimal; moderation can be added later via Filament.  
**Alternative considered:** `is_published = false` by default with admin approval — deferred.

### D3 — API returns all reviews, no cursor pagination
**Decision:** `GET /api/v1/reviews` returns up to 50 most recent reviews ordered by `created_at DESC`.  
**Rationale:** The reviews page is a simple grid, not infinite scroll. 50 is sufficient for demo data.  
**Alternative considered:** Cursor-paginated response — unnecessary complexity for current scope.

### D4 — Store in existing `api/v1` prefix
**Decision:** `ReviewController` lives at `App\Http\Controllers\Api\V1\ReviewController`, registered under `/api/v1/reviews`.  
**Rationale:** Consistent with all other API controllers in this project.

### D5 — `ReviewResource` for response shaping
**Decision:** Use a `ReviewResource` to expose `id`, `rating`, `title`, `body`, `patient_name` (anonymised — first name + last initial), `created_at`.  
**Rationale:** Patient privacy — expose only first name + last initial, not full name or email.

## Risks / Trade-offs

- **No moderation**: Inappropriate content can be submitted immediately. Mitigation: basic server-side length validation (body max 1000 chars).
- **One review per patient**: Power users may want to update their review. Mitigation: document as v1 limitation; `PUT` endpoint can be added later without schema changes.
- **Landing page API call**: Adds one more `onMounted` fetch to `LandingView`. Mitigation: silent empty state on failure; skeleton loading state shown.
