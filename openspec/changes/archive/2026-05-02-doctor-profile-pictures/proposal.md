## Why

Doctor cards on the landing page and the `/doctors` listing currently show only a name, specialisation, and bio — there is no visual identity. Adding a profile photo makes the UI more approachable, increases patient trust, and brings the clinic's online presence in line with expectations for a modern healthcare booking app.

## What Changes

- Add a nullable `photo_path` column (VARCHAR 255) to the `doctors` table to store the relative path of the uploaded photo
- Add file upload handling in the Filament admin so staff can upload/replace a doctor's photo
- Expose `photo_url` (an absolute URL resolved via `Storage::url()`) in `DoctorResource`
- Update the `Doctor` TypeScript interface in the SPA to include `photo_url: string | null`
- Display the photo as a circular avatar above the doctor's name in `DoctorsView.vue` and in the doctor list on `LandingView.vue`; fall back to a placeholder initials avatar when no photo is set
- Update `DoctorFactory` to generate a seed-friendly placeholder photo path

## Capabilities

### New Capabilities

- `doctor-profile-photo`: Storage, upload, and retrieval of a doctor's profile photo; API exposure as an absolute URL; avatar rendering in the SPA with initials fallback

### Modified Capabilities

- `spa-landing-page`: Doctor cards on the landing page now display a circular avatar above the doctor's name
- `spa-booking-ui`: The `DoctorSlotsView` may show doctor name/avatar at the top of the page (minor cosmetic, no behavioural change)

## Impact

- **Database**: New migration adds `doctors.photo_path` (nullable VARCHAR)
- **Storage**: Laravel `public` disk; photos stored under `doctors/` prefix; `php artisan storage:link` required on deploy
- **API**: `GET /api/v1/doctors` and `GET /api/v1/doctors/{id}` responses gain a `photo_url` field (additive, non-breaking)
- **Filament**: `DoctorResource` form gains a `FileUpload` field
- **SPA types**: `Doctor` interface extended with `photo_url`
- **Vue views**: `DoctorsView.vue` and `LandingView.vue` updated to render avatars
- **Factory/Seeder**: `DoctorFactory` updated to include a sample photo path
