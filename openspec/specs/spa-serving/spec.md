# SPA Serving Spec

## Requirements

### Requirement: SPA is served from Laravel for all patient-facing paths
Laravel SHALL serve the patient SPA HTML shell on any GET request whose path does not start with `admin` or `api`. The shell SHALL include the Vite-injected entry point via `@vite`.

#### Scenario: Patient visits root path
- **WHEN** a browser sends `GET /`
- **THEN** Laravel returns the `spa.blade.php` HTML shell with the SPA entry point included

#### Scenario: Patient visits a deep SPA route
- **WHEN** a browser sends `GET /appointments` or any nested path not starting with `admin` or `api`
- **THEN** Laravel returns the same `spa.blade.php` shell, and Vue Router handles the client-side path

#### Scenario: Admin path is not intercepted
- **WHEN** a request is made to `/admin` or any path starting with `/admin/`
- **THEN** Laravel does NOT serve the SPA shell; the request is handled by Filament

#### Scenario: API path is not intercepted
- **WHEN** a request is made to `/api` or any path starting with `/api/`
- **THEN** Laravel does NOT serve the SPA shell; the request is handled by API routes

### Requirement: SPA source is built by the root Vite pipeline
The `resources/spa/main.ts` entry point SHALL be registered in the root `vite.config.ts`. Running `npm run build` or `npm run dev` from the project root SHALL compile and serve all SPA assets.

#### Scenario: Build includes SPA assets
- **WHEN** `npm run build` is executed
- **THEN** the build output includes compiled SPA JS and CSS bundles without errors

#### Scenario: Hot Module Replacement works in dev
- **WHEN** `npm run dev` is running and a Vue file in `resources/spa/` is saved
- **THEN** the browser reflects the change without a full page reload

### Requirement: SPA Tailwind styles are processed by the root Tailwind v4 pipeline
The SPA's Vue and TypeScript files in `resources/spa/` SHALL be included as Tailwind content sources. All SPA utility classes SHALL be present in the compiled CSS.

#### Scenario: SPA utility classes appear in built CSS
- **WHEN** `npm run build` completes
- **THEN** Tailwind classes used in `resources/spa/` files are present in the output CSS

### Requirement: SPA API calls use a configurable base URL
The Axios instance inside the SPA SHALL read `import.meta.env.VITE_API_URL` as the API base URL, defaulting to `/api/v1` when the env variable is not set.

#### Scenario: Default base URL is relative
- **WHEN** `VITE_API_URL` is not set in the environment
- **THEN** all Axios requests use `/api/v1` as the base path (same-origin)

#### Scenario: Custom base URL overrides the default
- **WHEN** `VITE_API_URL=https://api.example.com` is set
- **THEN** all Axios requests use `https://api.example.com` as the base path
