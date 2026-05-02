## Context

The project has two separate frontend pipelines:
1. **Inertia app** (`resources/js/`) — staff-facing (Welcome, Login, Dashboard, Settings) backed by `inertiajs/inertia-laravel` and the `@inertiajs/vite` plugin. Served via Laravel's Vite pipeline on port 8000.
2. **Patient SPA** (`patient-spa/`) — standalone Vite 5 + Vue 3 + TypeScript SPA with its own `package.json`, Tailwind v3 (PostCSS), Vue Router 4, Pinia, and Axios. Served on port 5173, communicates with the Laravel API via Bearer tokens.

The Inertia staff app is largely superseded by Filament (which handles admin at `/admin`). The patient SPA is the only meaningful user-facing frontend. Running two separate Vite processes is unnecessary complexity.

## Goals / Non-Goals

**Goals:**
- One `npm run dev` (or `npm run build`) serves the entire frontend.
- Patient SPA source lives in `resources/spa/` inside the Laravel repo.
- SPA is served via a Blade shell on any non-admin, non-API path.
- SPA Tailwind styles are processed by the existing Tailwind v4 pipeline.
- Development experience is unchanged for the SPA (HMR still works).

**Non-Goals:**
- SSR for the patient SPA.
- Keeping any part of the Inertia frontend (it is removed).
- Changing API routes, Sanctum config, or CORS policy.
- Migrating SPA Tailwind v3 utility classes to v4 equivalents beyond what is required to make the build pass (v4 is backwards-compatible for common utilities).

## Decisions

### D1 — Merge into existing Vite pipeline (not a new Vite config)

**Decision:** Add the SPA entry point to the existing `vite.config.ts` rather than creating a separate Vite project inside `resources/`.

**Rationale:** The root `vite.config.ts` already has `laravel-vite-plugin`, `@vitejs/plugin-vue`, and `@tailwindcss/vite`. Adding one more input entry and a resolve alias is sufficient. A separate config would re-introduce the two-process problem.

**Alternative considered:** Keep `patient-spa/` as a workspace package. Rejected — still two build systems, just linked.

---

### D2 — `@spa` alias, not overwriting `@`

**Decision:** Add `resolve.alias['@spa']` → `resources/spa/src` and update all SPA imports to use `@spa/`. The existing `@` → `resources/js` mapping is left intact.

**Rationale:** Although the Inertia app is being removed, overwriting `@` risks breaking any incidental references that remain (e.g., shared composables, auth helpers) until cleanup is verified. A distinct alias makes the migration atomic — all `@spa/` imports are SPA-specific by convention.

**Note:** After the Inertia removal is confirmed clean, `@` can be reassigned to `resources/spa/src` in a follow-up; for now `@spa` is the safe choice.

---

### D3 — Tailwind v4 `@source` directive, no separate config

**Decision:** Add `@source '../../resources/spa/**'` to `resources/css/app.css` (the existing Tailwind v4 stylesheet) instead of keeping a `tailwind.config.js` inside `resources/spa/`.

**Rationale:** Tailwind v4 auto-detects content via `@source` CSS directives. The `patient-spa/tailwind.config.js` (v3 PostCSS approach) is incompatible with the root `@tailwindcss/vite` plugin. All SPA utility classes will be picked up through the single app.css.

---

### D4 — Blade catch-all route at the bottom of `web.php`

**Decision:** A single catch-all route `Route::get('/{any}', ...)` with regex `^(?!admin|api).*` is placed at the bottom of `web.php` after all Fortify/Inertia-era routes are removed.

**Rationale:** Vue Router handles all client-side navigation. Laravel only needs to return the SPA shell HTML for any first load. The regex excludes `/admin*` (Filament) and `/api*` (JSON API) from being caught.

---

### D5 — Axios baseURL via env variable

**Decision:** Change `baseURL` in `axios.ts` from the hardcoded `http://localhost:8000/api/v1` to `import.meta.env.VITE_API_URL ?? '/api/v1'`.

**Rationale:** When served from the same Laravel origin, relative `/api/v1` works in both dev and production without configuration. An env override (`VITE_API_URL`) is available for cases where the API is on a different domain.

---

### D6 — Remove Inertia plugin and routes

**Decision:** Remove `@inertiajs/vite` from `vite.config.ts`, delete Inertia page files from `resources/js/pages/`, and remove all Inertia-specific routes from `web.php` (Welcome, Login, Dashboard, Settings, ConfirmPassword, etc.).

**Rationale:** No part of the application uses Inertia once the SPA takes over. Keeping the plugin and unused routes is dead weight and a maintenance hazard. Fortify routes (POST `/login`, POST `/register`, etc.) remain untouched — the SPA calls them via Axios.

## Risks / Trade-offs

- **Tailwind v3 → v4 class compatibility** → Tailwind v4 is mostly backwards-compatible for standard utilities. Risk is limited to custom v3 config (e.g., `extend.colors`) that was in `patient-spa/tailwind.config.js`. Review that file before deleting and replicate any custom theme in `app.css` using `@theme`.

- **Fortify routes still handle auth POST endpoints** → The SPA hits `/login`, `/register` etc. via Axios. Removing Inertia routes must not accidentally remove Fortify's route registrations (those come from `FortifyServiceProvider`, not `web.php`).

- **`resources/js/` leftover imports** → Some utility files in `resources/js/` (composables, types) may be safe to delete once Inertia pages are removed. If they are inadvertently left, the build still passes but bundle size increases. Audit during implementation.

## Migration Plan

1. Copy `patient-spa/src/` to `resources/spa/src/` (no functional changes yet).
2. Update all SPA `@/` imports to `@spa/`.
3. Update root `vite.config.ts`: add entry, add alias, remove Inertia plugin.
4. Update root `tsconfig.json`: add `@spa/*` path.
5. Update `resources/css/app.css`: add `@source` for SPA files.
6. Create `resources/views/spa.blade.php`.
7. Update `routes/web.php`: remove Inertia routes, add catch-all.
8. Update `resources/spa/src/api/axios.ts`: use `VITE_API_URL ?? '/api/v1'`.
9. Run `npm run build` — verify clean build.
10. Delete `patient-spa/` directory.
11. Run full test suite to confirm no regressions.

**Rollback:** `patient-spa/` is deleted only after a clean build and passing tests. Until then it is untouched, so rollback is `git revert` of the vite config and route changes.

## Open Questions

- None — all decisions above are unambiguous given the codebase state.
