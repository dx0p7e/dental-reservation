## Why

All Vue frontend strings are currently hardcoded — some in English, some already in Lithuanian (the navbar links). This inconsistency makes the UI feel unfinished and prevents adding a second locale cleanly. For a Lithuanian dental clinic, Lithuanian must be the primary language of the UI. Adding a structured i18n layer now also demonstrates internationalisation as a system quality attribute in the thesis.

## What Changes

- **`vue-i18n` v9** installed as a new npm dependency — the de-facto standard composition-API-compatible i18n library for Vue 3
- **`resources/spa/locales/en.json`** — all UI string keys with English translations
- **`resources/spa/locales/lt.json`** — all UI string keys with Lithuanian translations
- **`resources/spa/plugins/i18n.ts`** — plugin setup in composition API mode (`legacy: false`); default locale `lt`; fallback `en`; locale persisted to `localStorage` key `'locale'`
- **`resources/spa/main.ts`** — registers the i18n plugin
- **`resources/spa/components/AppNavbar.vue`** — language switcher (LT / EN toggle) added to the right side of the navbar; all hardcoded strings replaced with `t()` calls; `publicLinks` array items use i18n keys
- **All Vue view files** — hardcoded UI strings replaced with `t()` calls; `useI18n()` imported where needed
- **`tsconfig.json`** — `resolveJsonModule: true` added if not already present so JSON locale files can be imported

## Capabilities

### New Capabilities
- `i18n-lt-en`: Full Lithuanian/English UI with persistent user locale selection

### Modified Capabilities
- `public-navbar`: Language switcher added; all navbar strings internationalised
- `registration-flow`: RegisterView strings translated
- `spa-landing-page`: Landing page strings translated
- `spa-booking-ui`: BookView strings translated
- `spa-appointments-ui`: AppointmentsView and RescheduleView strings translated

## Impact

- `package.json` — add `vue-i18n` dependency
- `resources/spa/plugins/i18n.ts` — new file
- `resources/spa/locales/en.json` — new file
- `resources/spa/locales/lt.json` — new file
- `resources/spa/main.ts` — import and register i18n plugin
- `resources/spa/components/AppNavbar.vue` — language switcher + `t()` replacements
- All 17 Vue view/component files — `useI18n()` + `t()` replacements
- `tsconfig.json` — ensure `resolveJsonModule: true`
- No backend changes — API data (names, descriptions) is real data, not UI string
- No new routes
- No database changes
