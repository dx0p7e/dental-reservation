## Context

The patient SPA is a standalone Vue 3 + Vue Router application in `resources/spa/`. It is distinct from the Inertia.js pages in `resources/js/pages/` — it has its own entry point (`resources/spa/main.ts`), its own Pinia stores, and communicates with the backend via a Sanctum-authenticated Axios client (`resources/spa/api/axios.ts`).

Current state: all views in `resources/spa/views/` are functional but unstyled — plain HTML with minimal Tailwind utility classes. There are no shared layout components in the SPA layer.

**Existing assets relevant to this change:**
- `resources/js/components/ui/` — shadcn/vue components (Badge, Button, Card, etc.) built for the Inertia layer. These can be imported in SPA views via the `@` alias.
- `resources/css/app.css` — single Tailwind v4 stylesheet shared by both Inertia and SPA layers. Custom tokens live here inside `@theme inline {}` and `:root {}`.
- No `tailwind.config.js` — Tailwind v4 is configured entirely in CSS.

**Constraint:** The SPA uses `@spa` alias for `resources/spa/` and `@` for `resources/js/`. Inter font is not yet imported; Instrument Sans is the current sans-serif.

## Goals / Non-Goals

**Goals:**
- Define 7 clinic colour tokens (clinic-dark, clinic-blue, clinic-teal, clinic-surface, clinic-border, clinic-text, clinic-muted) in `resources/css/app.css`
- Import Inter from Google Fonts in the SPA HTML template or `app.css`
- Rebuild `LandingView.vue` with all six sections (Navbar, Hero, Services, WhyUs, HowItWorks, Testimonials, CTA, Footer)
- Redesign `DoctorSlotsView.vue`, `BookView.vue`, `RequestBookingView.vue`, `AppointmentsView.vue`, `LoyaltyView.vue` with the clinic design system
- Create `resources/spa/components/AppNavbar.vue`, `PageHeader.vue`, `StatusBadge.vue`

**Non-Goals:**
- Mobile-first responsive design beyond basic Tailwind breakpoints
- Animations, transitions, or dark mode
- Admin panel (Filament) visual changes
- New API endpoints or backend logic
- Changes to Vue Router routes, Pinia stores, or TypeScript types
- Changing Inertia pages or auth pages (`LoginView.vue`, `RegisterView.vue`)

## Decisions

### D1: Token definition location — `app.css` not `tailwind.config.js`

Tailwind v4 removes `tailwind.config.js` in favour of CSS-native config. Tokens must be added inside `@theme inline {}` in `resources/css/app.css` as `--color-clinic-dark`, `--color-clinic-blue`, etc. This makes them available as `bg-clinic-dark`, `text-clinic-teal`, etc. via Tailwind's token naming convention.

**Alternative considered:** A separate `resources/spa/spa.css` file — rejected because it introduces a second stylesheet entry point and breaks the shared `app.css` convention already established.

### D2: New SPA components in `resources/spa/components/` not `resources/js/components/ui/`

`AppNavbar`, `PageHeader`, and `StatusBadge` are SPA-specific (they use Vue Router `<RouterLink>`, SPA auth store, and SPA-specific status enums). Placing them under `resources/spa/components/` keeps the SPA self-contained. The existing shadcn/ui components remain usable via `@` import when convenient (e.g., reusing `<Badge>` inside `StatusBadge.vue`).

**Alternative considered:** Adding them to `resources/js/components/ui/` — rejected because those components are designed for the Inertia/server-rendered context.

### D3: Inter font via Google Fonts `@import` in `app.css`

Adding `@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');` to `app.css` and extending the `--font-sans` token to include Inter as the first option is the lowest-friction approach. No build-time font bundling needed.

**Alternative considered:** `npm install @fontsource/inter` — valid but adds a dependency and requires a vite config change. Rejected for simplicity since this is a demo application.

### D4: Reuse existing shadcn/ui `<Badge>` and `<Card>` components inside SPA views

The shadcn/ui components in `resources/js/components/ui/` are accessible via `@/components/ui/`. SPA views can import them directly. `StatusBadge.vue` wraps the existing `<Badge>` with the status→colour mapping logic to avoid reimplementing badge styling.

### D5: Services section data is hardcoded on the landing page

`LandingView.vue` is a public page (no auth required). Making an API call to `/api/services` from the landing page adds complexity. For the demo, 3–4 representative services are hardcoded in the component. The real service selection happens inside the authenticated `DoctorSlotsView`.

**Alternative considered:** Fetching from `/api/services` — valid but adds loading state complexity to the landing page for data that is static in demo context.

## Risks / Trade-offs

- **Shared `app.css`** → clinic tokens are globally available across both Inertia pages and SPA. Risk: naming conflicts with existing token names. Mitigation: use `clinic-` prefix throughout to namespace all new tokens.
- **Inter from Google Fonts** → requires internet access at load time. Mitigation: acceptable for thesis demo; fallback chain (`ui-sans-serif, system-ui`) still renders cleanly.
- **No Inertia layout awareness** → `AppNavbar.vue` duplicates nav logic from `resources/js/components/AppHeader.vue`. Mitigation: SPA and Inertia layers are intentionally separate stacks; duplication is intentional and documented.
- **shadcn/ui `<Badge>` import in SPA** → the Badge component uses shadcn class-variance-authority (CVA) variants that may conflict with clinic design tokens. Mitigation: `StatusBadge.vue` applies colour classes via `:class` binding, overriding the default shadcn variant.
