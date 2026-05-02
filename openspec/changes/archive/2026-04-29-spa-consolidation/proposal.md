## Why

The patient booking SPA was scaffolded as a standalone Vite project (`patient-spa/`) requiring a separate `npm run dev` process and separate server port. Consolidating it into the existing Laravel Vite pipeline eliminates the split build pipeline, serves everything from one origin, and simplifies local development and deployment.

## What Changes

- **BREAKING** Remove `patient-spa/` directory entirely after migrating source files into `resources/spa/`.
- **BREAKING** Remove existing Inertia application (routes at `/`, `/login`, `/dashboard`, `/settings/*`) — the patient SPA becomes the sole user-facing frontend. Filament remains at `/admin*`.
- Move `patient-spa/src/` → `resources/spa/` (Vue files, stores, router, types, api).
- Add `resources/views/spa.blade.php` as the SPA shell (replaces `patient-spa/index.html`).
- Register `resources/spa/main.ts` as the SPA entry point in the root `vite.config.ts`.
- Add `@spa` alias (`resources/spa/src`) in `vite.config.ts` and `tsconfig.json` (keeps existing `@` → `resources/js` intact for any remaining references; SPA imports use `@spa/`).
- Add `@source 'resources/spa/**'` directive to `resources/css/app.css` (Tailwind v4 — no separate `tailwind.config.js` needed).
- Remove `patient-spa/tailwind.config.js` and PostCSS dependencies; the root Tailwind v4 pipeline covers SPA styles.
- Add catch-all web route (below all existing routes) serving `spa.blade.php` for all paths not starting with `admin` or `api`.
- Update SPA Axios `baseURL` to use `import.meta.env.VITE_API_URL ?? '/api/v1'` so it works correctly when served from the same Laravel origin.
- Remove `inertiajs/inertia-laravel` plugin from `vite.config.ts` and Inertia page files from `resources/js/pages/` (Inertia is no longer needed for the patient-facing frontend).

## Capabilities

### New Capabilities

- `spa-serving`: Laravel serves the patient SPA as a Blade shell with a Vite-injected entry point; a web catch-all route handles all patient-facing paths.

### Modified Capabilities

<!-- None — no existing specs to modify. -->

## Impact

- **`vite.config.ts`**: Add `resources/spa/main.ts` input; add `@spa` resolve alias; remove Inertia plugin.
- **`tsconfig.json`**: Add `@spa/*` path mapping.
- **`resources/css/app.css`**: Add `@source 'resources/spa/**'` directive.
- **`resources/views/spa.blade.php`**: New Blade shell view.
- **`routes/web.php`**: Remove existing Inertia routes; add SPA catch-all.
- **`patient-spa/`**: Deleted after migration.
- **`resources/spa/`**: New home for all patient SPA source files.
- **`resources/js/`**: Inertia app entry and pages removed (or left as dead code if other server-side behaviour depends on them — assess at implementation time).
- **`resources/spa/src/api/axios.ts`**: Update `baseURL` to `import.meta.env.VITE_API_URL ?? '/api/v1'`.
- No API, database, or Sanctum changes.
