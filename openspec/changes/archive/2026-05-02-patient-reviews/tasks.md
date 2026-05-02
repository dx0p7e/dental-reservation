## 1. Migration & Model

- [x] 1.1 Run `php artisan make:migration create_patient_reviews_table` — columns: `id`, `patient_id` (FK nullable → users, SET NULL on delete), `rating` (tinyint, not null), `title` (string 150, nullable), `body` (text, not null), `is_published` (boolean, default true), timestamps; add `UNIQUE(patient_id)` constraint
- [x] 1.2 Run `php artisan make:model PatientReview` — add `$fillable = ['patient_id', 'rating', 'title', 'body', 'is_published']`; add `belongsTo(User::class, 'patient_id')` relationship
- [x] 1.3 Add `hasOne(PatientReview::class, 'patient_id')` relationship to `User` model
- [x] 1.4 Run `php artisan migrate`

## 2. Factory & Seeder

- [x] 2.1 Run `php artisan make:factory PatientReviewFactory --model=PatientReview` — `rating`: `fake()->numberBetween(4, 5)`, `title`: null (override in seeder), `body`: `fake()->paragraph()`, `is_published`: true
- [x] 2.2 Create `ReviewSeeder` (`php artisan make:seeder ReviewSeeder`) — seed at least 6 reviews in Lithuanian linked to the 4 demo patient users; vary ratings (mix of 4 and 5 stars); use realistic Lithuanian titles and body texts
- [x] 2.3 Add `ReviewSeeder::class` to the `$this->call([...])` array in `DemoSeeder`

## 3. API — ReviewResource & ReviewController

- [x] 3.1 Run `php artisan make:resource Api/V1/ReviewResource` — expose: `id`, `rating`, `title`, `body`, `patient_name` (first name + last initial of `$this->patient`), `created_at`
- [x] 3.2 Run `php artisan make:controller Api/V1/ReviewController` — implement `index()`: return `ReviewResource::collection(PatientReview::where('is_published', true)->latest()->limit(50)->get())`; implement `store(Request $request)`: validate (`rating` int 1–5 required, `body` string 10–1000 required, `title` string max 150 nullable), check for existing review (409 if found), create and return `ReviewResource` with 201
- [x] 3.3 Register routes in `routes/api.php` under the `v1` prefix: `Route::get('reviews', ...)` (public) and `Route::post('reviews', ...)` (auth:sanctum)

## 4. TypeScript Types

- [x] 4.1 Add `Review` interface to `resources/spa/types/index.ts`: `{ id: number; rating: number; title: string | null; body: string; patient_name: string; created_at: string }`

## 5. ReviewCard Component

- [x] 5.1 Create `resources/spa/components/ReviewCard.vue` — props: `review: Review`; displays: filled star icons (★) for `review.rating` in `text-clinic-teal`, `review.title` if present, `review.body`, `review.patient_name`, `review.created_at` formatted as short date (e.g. `dd/MM/yyyy`)

## 6. ReviewForm Component

- [x] 6.1 Create `resources/spa/components/ReviewForm.vue` — modal overlay; props: none; emits: `submitted(review: Review)`, `cancel`; contains: interactive star picker (1–5, click to set), optional title input (max 150), required body textarea (min 10, max 1000), submit + cancel buttons; calls `POST /api/v1/reviews`; shows inline API validation errors; closes and emits `submitted` on success

## 7. ReviewsView Page

- [x] 7.1 Create `resources/spa/views/ReviewsView.vue` — fetches `GET /api/v1/reviews` on mount; renders heading "Atsiliepimai"; renders reviews in a `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6` using `ReviewCard`; shows 3 skeleton cards while loading; if authenticated and no existing review → show "Palikti atsiliepimą" button; if not authenticated → show login nudge text; on `ReviewForm` `submitted` event → prepend new review to list; check if current user already reviewed by comparing API results to auth store user id (if available) or track via a `hasReviewed` ref that becomes true after submission
- [x] 7.2 Add `/reviews` route to `resources/spa/router/index.ts`: `{ path: '/reviews', component: () => import('@spa/views/ReviewsView.vue') }`
- [x] 7.3 Add `nav.reviews` translation key to both locale files — `lt.json`: `"nav.reviews": "Atsiliepimai"`, `en.json`: `"nav.reviews": "Reviews"`
- [x] 7.4 Add `{ labelKey: 'nav.reviews', to: '/reviews' }` to the `publicLinks` array in `AppNavbar.vue` after the `nav.about` entry

## 8. Landing Page — Live Reviews

- [x] 8.1 In `LandingView.vue`: remove the hardcoded `testimonials` array; add `reviews` ref and `loadingReviews` ref; fetch `GET /api/v1/reviews` on mount (silent failure → empty array); replace the testimonials `v-for` loop with `v-for="review in reviews.slice(0, 3)"` using `ReviewCard`; show skeleton during loading; hide section if `reviews.length === 0`; add `RouterLink` to `/reviews` below the cards

## 9. Tests

- [x] 9.1 Add `GET /api/v1/reviews` test — asserts 200, `data` array present, `patient_name` is anonymised (contains only first name + initial), unpublished reviews excluded
- [x] 9.2 Add `POST /api/v1/reviews` test — asserts 201 on valid submission; 409 on duplicate; 422 on invalid rating/body; 401 when unauthenticated
- [x] 9.3 Run `php artisan test --compact` and confirm all tests pass

## 10. Code Quality

- [x] 10.1 Run `vendor/bin/pint --dirty --format agent` and fix any style issues
