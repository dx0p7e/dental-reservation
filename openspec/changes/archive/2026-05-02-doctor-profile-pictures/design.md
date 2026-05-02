## Context

The `doctors` table currently has no photo column. `DoctorResource` (API) exposes `id`, `name`, `specialization`, `bio`, and loaded `services` — no image. Both `LandingView.vue` and `DoctorsView.vue` render doctor cards with text only. Filament's `DoctorForm` has no file upload field.

Laravel's `public` storage disk is already configured. `php artisan storage:link` must be run (once) on each environment; this is standard practice on this project.

## Goals / Non-Goals

**Goals:**
- Store one optional profile photo per doctor (nullable, replaceable)
- Expose a fully-qualified `photo_url` from the API (absolute URL, `null` if no photo)
- Render a circular avatar in `DoctorsView` and the landing page doctor section; fall back to initials when no photo is present
- Allow admin staff to upload/replace a photo through Filament

**Non-Goals:**
- Image resizing or thumbnail generation (serve original; browser CSS handles sizing)
- Multiple photos per doctor
- Public patient-facing photo upload
- CDN / S3 integration (out of scope; use local `public` disk)

## Decisions

### D1 — Store path, expose URL
Store a relative path (`doctors/{filename}`) in `doctors.photo_path`. `DoctorResource` computes the absolute URL at serialisation time via `Storage::disk('public')->url($this->photo_path)`. This keeps the DB agnostic of domain/environment while always returning a usable URL to the frontend.

**Alternative considered**: store the full URL directly — rejected because it couples DB data to the deployment domain and breaks on environment changes.

### D2 — Laravel `public` disk, `doctors/` subdirectory
Photos are stored at `storage/app/public/doctors/{uuid}.{ext}` and served via the symlinked `public/storage/` path. The `doctors/` prefix keeps doctor photos isolated from other uploads.

**Alternative considered**: a dedicated disk — unnecessary complexity for a single upload type.

### D3 — Filament `FileUpload` component
Use Filament v4's `FileUpload::make('photo_path')` with `->disk('public')->directory('doctors')->image()->imageEditor(false)`. This handles upload, deletion of the old file on replacement, and preview out of the box.

### D4 — Initials avatar fallback in Vue
When `photo_url` is `null`, render a `<div>` with the doctor's initials (first letter of first name + first letter of last name) on a `bg-clinic-teal text-white` background. This avoids a broken-image icon and requires no external placeholder service.

**Alternative considered**: a generic silhouette SVG — initials are more personalised and don't require an extra asset.

### D5 — `photo_url` is additive in the API response
Adding `photo_url` to `DoctorResource` is a non-breaking change. Existing consumers that ignore unknown fields are unaffected.

## Risks / Trade-offs

- **Storage not linked on new environments** → `php artisan storage:link` must be in deploy scripts. Document in README / deployment notes.
- **Large photo uploads** → Filament's `FileUpload` respects PHP's `upload_max_filesize` / `post_max_size`. No additional validation beyond "must be an image" is added (acceptable for an admin-only form).
- **Old photos not cleaned up if `photo_path` is set to null via DB** → Minor; orphaned files in `storage/app/public/doctors/` don't affect functionality.

## Migration Plan

1. Run `php artisan migrate` — adds `doctors.photo_path` (nullable, no default)
2. Run `php artisan storage:link` if not already done
3. Deploy application — existing doctor records have `photo_path = null`, `photo_url = null` in API; UI falls back to initials avatar
4. Admin uploads photos via Filament at their own pace — no forced data migration needed
