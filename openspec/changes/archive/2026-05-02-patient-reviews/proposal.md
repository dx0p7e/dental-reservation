## Why

The landing page currently shows three hardcoded, fake testimonials. Patients have no way to share their real experience, and visitors see fabricated reviews. Replacing hardcoded content with a real review system increases credibility and patient engagement.

## What Changes

- New `patient_reviews` database table storing patient-authored reviews (rating, title, body, created_at).
- New `PatientReview` Eloquent model and factory.
- New `ReviewSeeder` with realistic Lithuanian-language seed data.
- New `GET /api/v1/reviews` API endpoint returning published reviews (latest first, paginated).
- Landing page testimonials section replaced with live data from the API; shows the 3 most recent reviews plus a "Peržiūrėti visas atsiliepimus →" link.
- New `/reviews` public SPA page displaying all reviews in a grid with pagination.
- Authenticated patients can submit a review via a modal/form triggered by an "Palikti atsiliepimą" button on the reviews page.
- New `POST /api/v1/reviews` endpoint (auth required); one review per patient (unique constraint).

## Capabilities

### New Capabilities
- `patient-reviews`: Patient review submission and public display — DB table, model, API endpoints, SPA reviews page, review form modal.

### Modified Capabilities
- `spa-landing-page`: The testimonials section requirement changes from hardcoded content to live API data with a "view all" link.

## Impact

- **Database**: New migration for `patient_reviews` table.
- **API**: Two new routes under `api/v1/reviews` (index + store), registered in `routes/api.php`.
- **Models**: `PatientReview` model; `User` gets a `hasOne` reviews relationship.
- **SPA**: New `ReviewsView.vue` page, new `ReviewCard.vue` and `ReviewForm.vue` components, updated `LandingView.vue` testimonials section, new route in the Vue router.
- **Seeders**: `ReviewSeeder` added to `DemoSeeder` call chain.
- **Wayfinder**: New controller actions will generate new typed functions.
