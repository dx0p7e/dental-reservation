## 1. Migrate SPA Source Files

- [x] 1.1 Copy `patient-spa/src/` to `resources/spa/src/` (all Vue files, stores, router, types, api)
- [x] 1.2 Review `patient-spa/tailwind.config.js` for any custom theme extensions (colors, spacing, etc.) and note them for T2.3

## 2. Root Build Pipeline

- [x] 2.1 Update `vite.config.ts`: add `resources/spa/main.ts` to `laravel.input`, add `resolve.alias['@spa']` → `fileURLToPath(new URL('resources/spa/src', import.meta.url))`, remove `inertia()` plugin import and usage
- [x] 2.2 Update `tsconfig.json`: add `"@spa/*": ["resources/spa/src/*"]` to `compilerOptions.paths`
- [x] 2.3 Update `resources/css/app.css`: add any custom theme tokens from T1.2 as a `@theme` block. Only add `@source '../spa/**';` if T6.1 reveals missing Tailwind classes — `@tailwindcss/vite` auto-detects files in the Vite module graph, so the directive may not be needed

## 3. Update SPA Source for New Location

- [x] 3.1 Replace all `@/` import aliases in `resources/spa/src/` with `@spa/`
- [x] 3.2 Update `resources/spa/src/api/axios.ts`: change `baseURL` to `import.meta.env.VITE_API_URL ?? '/api/v1'`

## 4. Laravel Integration

- [x] 4.1 Create `resources/views/spa.blade.php` with Vite entry point via `@vite(['resources/css/app.css', 'resources/spa/main.ts'])`
- [x] 4.2 Update `routes/web.php`: remove all Inertia routes (Welcome, Login, ConfirmPassword, Dashboard) and add SPA catch-all at the bottom: `Route::get('/{any}', fn () => view('spa'))->where('any', '^(?!admin|api).*')`

## 5. Cleanup

- [x] 5.1 Remove unused Inertia-related imports from `vite.config.ts` (`import inertia from '@inertiajs/vite'`)
- [x] 5.2 Delete `resources/js/pages/` directory (Inertia page components)
- [x] 5.3 Remove `patient-spa/` directory entirely

## 6. Verify

- [x] 6.1 Run `npm run build` — confirm zero errors and SPA assets in manifest; inspect output CSS to verify SPA Tailwind classes are present (if any are missing, add `@source '../spa/**';` to `resources/css/app.css`)
- [x] 6.2 Run `php artisan test --compact` — confirm no regressions
- [x] 6.3 Run `vendor/bin/pint --dirty --format agent` on any modified PHP files
- [x] 6.4 Smoke test: visit `/` and confirm the patient SPA landing page loads (not Inertia Welcome)
- [x] 6.5 Smoke test: visit `/admin` and confirm Filament loads correctly
- [x] 6.6 Smoke test: patient login via `/login` → Vue Router Login page, not Inertia login
