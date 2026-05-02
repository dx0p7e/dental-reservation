# Design — Vue I18n with English and Lithuanian

## Architecture Overview

The change adds a thin i18n layer over the existing SPA without restructuring any routing, stores, or API calls. API data (doctor names, service names, descriptions) is real user-generated content and is NOT translated — only UI chrome strings go through `t()`.

## Dependency

```bash
npm install vue-i18n@9 --save
```

`vue-i18n` v9 is the Vue 3–compatible version. It must be `^9.x` — v10+ has breaking changes; v8 is Vue 2–only.

## File Structure (new files)

```
resources/spa/
├── plugins/
│   └── i18n.ts        ← plugin factory
└── locales/
    ├── en.json        ← English translations
    └── lt.json        ← Lithuanian translations
```

## Plugin Setup — `resources/spa/plugins/i18n.ts`

```typescript
import { createI18n } from 'vue-i18n'
import en from '@spa/locales/en.json'
import lt from '@spa/locales/lt.json'

const savedLocale = localStorage.getItem('locale') ?? 'lt'

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, lt },
})
```

- `legacy: false` enables composition API mode (`useI18n()` with `t()`)
- Default locale: `lt` (Lithuanian — the clinic's primary audience)
- Fallback: `en` — any missing Lithuanian key falls back to English without error
- Locale persisted to `localStorage` key `'locale'`; this is locale preference only, separate from any other `localStorage` usage

## Registration — `resources/spa/main.ts`

```typescript
import { i18n } from '@spa/plugins/i18n'
// ...
app.use(i18n)
```

Add `app.use(i18n)` after `app.use(createPinia())` and before `app.mount('#app')`.

## TypeScript — `tsconfig.json`

Ensure `"resolveJsonModule": true` is in `compilerOptions` so that JSON locale files can be imported with full type inference.

## Key Namespace Structure

All keys follow `namespace.sub-key` flat-ish pattern. No deeply nested objects.

| Namespace | Covers |
|-----------|--------|
| `nav.*` | AppNavbar — all links, buttons, dropdown items |
| `landing.*` | LandingView — hero, stats, steps, CTA, testimonials |
| `login.*` | LoginView |
| `register.*` | RegisterView |
| `doctors.*` | DoctorsView |
| `slots.*` | DoctorSlotsView |
| `services.*` | ServicesView |
| `booking.*` | BookView (confirm booking, discount, loyalty) |
| `appointments.*` | AppointmentsView |
| `reschedule.*` | RescheduleView |
| `requestBooking.*` | RequestBookingView |
| `loyalty.*` | LoyaltyDashboardView + LoyaltyMarketingView |
| `profile.*` | ProfileView |
| `about.*` | AboutView |
| `contact.*` | ContactView |
| `privacy.*` | PrivacyView |
| `common.*` | Shared — loading spinners, error messages, retry buttons |

## Language Switcher — `AppNavbar.vue`

Added to the right side of the navbar, immediately before the user dropdown / guest buttons:

```html
<div class="flex items-center gap-1 text-sm">
  <button
    v-for="lang in ['lt', 'en']"
    :key="lang"
    @click="setLocale(lang)"
    :class="locale === lang
      ? 'font-semibold text-clinic-teal'
      : 'text-white/60 hover:text-white'"
    class="uppercase tracking-wide px-1"
  >
    {{ lang }}
  </button>
</div>
```

Script setup additions:

```typescript
const { t, locale } = useI18n()

function setLocale(lang: string) {
  locale.value = lang
  localStorage.setItem('locale', lang)
}
```

## `publicLinks` Array Pattern

The current `publicLinks` array stores hardcoded Lithuanian label strings. Replace the `label` field with an i18n key, then call `t()` in the template:

```typescript
// Before
const publicLinks = [
  { label: 'Pagrindinis', to: '/' },
  ...
]

// After
const publicLinks = [
  { labelKey: 'nav.home', to: '/' },
  { labelKey: 'nav.doctors', to: '/doctors' },
  { labelKey: 'nav.services', to: '/services' },
  { labelKey: 'nav.loyalty', to: '/loyalty' },
  { labelKey: 'nav.about', to: '/about' },
  { labelKey: 'nav.contact', to: '/contact' },
]
```

Template: `{{ t(link.labelKey) }}`

## Component Usage Pattern

Every component that has hardcoded UI strings:

```typescript
// Add to script setup
const { t } = useI18n()
```

Template usage:
```html
<!-- Plain string -->
{{ t('booking.title') }}

<!-- Named interpolation -->
{{ t('doctors.bookWith', { name: doctor.name }) }}

<!-- Conditional text (loading state) -->
{{ loading ? t('common.loading') : t('login.submit') }}
```

## Complete Locale Key Catalogue

### `en.json` (English — fallback)

```json
{
  "nav.home": "Home",
  "nav.doctors": "Doctors",
  "nav.services": "Services",
  "nav.loyalty": "Loyalty",
  "nav.about": "About",
  "nav.contact": "Contact",
  "nav.continueBooking": "Continue booking",
  "nav.book": "Book",
  "nav.appointments": "My appointments",
  "nav.myLoyalty": "My loyalty",
  "nav.profile": "My profile",
  "nav.logout": "Log out",
  "nav.login": "Log in",
  "nav.register": "Register",

  "common.loading": "Loading…",
  "common.error": "Something went wrong.",
  "common.retry": "Retry",
  "common.back": "Back",
  "common.save": "Save",
  "common.saving": "Saving…",
  "common.cancel": "Cancel",
  "common.noData": "No data available.",

  "landing.hero.title": "Your smile is our priority",
  "landing.hero.subtitle": "Book an appointment with our experienced dental team quickly and easily.",
  "landing.hero.cta": "Book an appointment",
  "landing.stats.experience": "Years Experience",
  "landing.stats.patients": "Patients Treated",
  "landing.stats.equipment": "Equipment",
  "landing.stats.procedures": "Procedures",
  "landing.steps.title": "How it works",
  "landing.steps.1": "Create Account",
  "landing.steps.2": "Choose a Service & Slot",
  "landing.steps.3": "Get Confirmed",
  "landing.doctors.title": "Our Doctors",
  "landing.doctors.book": "Book",
  "landing.doctors.loading": "Loading doctors…",
  "landing.services.title": "Our Services",
  "landing.services.viewAll": "View all services →",
  "landing.testimonials.title": "What our patients say",
  "landing.cta.title": "Ready to book?",
  "landing.cta.button": "Book an appointment",

  "login.title": "Log in",
  "login.email": "Email",
  "login.password": "Password",
  "login.submit": "Log in",
  "login.submitting": "Logging in…",
  "login.noAccount": "Don't have an account?",
  "login.register": "Register",

  "register.title": "Create an account",
  "register.name": "Name",
  "register.email": "Email",
  "register.phone": "Phone number",
  "register.password": "Password",
  "register.confirmPassword": "Confirm Password",
  "register.consent": "I agree to the {link} and consent to processing of my personal data.",
  "register.privacyPolicy": "Privacy Policy",
  "register.submit": "Register",
  "register.submitting": "Creating account…",
  "register.alreadyHave": "Already have an account?",
  "register.login": "Log in",

  "doctors.title": "Our Doctors",
  "doctors.book": "Book",
  "doctors.loading": "Loading doctors…",
  "doctors.empty": "No doctors available.",

  "slots.title": "Available slots",
  "slots.loading": "Loading slots…",
  "slots.empty": "No available slots for this period.",
  "slots.select": "Select slot",
  "slots.selected": "Selected",
  "slots.continue": "Continue",
  "slots.selectService": "Select a service",

  "services.title": "Our Services",
  "services.loading": "Loading services…",
  "services.empty": "No services available at this time.",
  "services.error": "Could not load services.",
  "services.retry": "Retry",
  "services.from": "From",

  "booking.title": "Confirm Booking",
  "booking.doctor": "Doctor",
  "booking.service": "Service",
  "booking.slot": "Date & Time",
  "booking.price": "Price",
  "booking.discount": "Discount",
  "booking.total": "Total",
  "booking.saved": "You saved €{amount}",
  "booking.loyaltyPoints": "You will earn {points} loyalty points",
  "booking.confirm": "Confirm booking",
  "booking.confirming": "Confirming…",
  "booking.profileIncomplete": "Complete your profile to unlock loyalty discounts.",
  "booking.verifyProfile": "Verify profile",
  "booking.loadingPreview": "Calculating price…",

  "appointments.title": "My Appointments",
  "appointments.empty": "You have no appointments yet.",
  "appointments.book": "Book an appointment",
  "appointments.status.pending": "Pending",
  "appointments.status.confirmed": "Confirmed",
  "appointments.status.cancelled": "Cancelled",
  "appointments.status.completed": "Completed",
  "appointments.cancel": "Cancel",
  "appointments.reschedule": "Reschedule",
  "appointments.loading": "Loading appointments…",

  "reschedule.title": "Reschedule Appointment",
  "reschedule.submit": "Confirm reschedule",
  "reschedule.submitting": "Rescheduling…",
  "reschedule.loading": "Loading…",
  "reschedule.empty": "No available slots.",

  "requestBooking.title": "Request an Appointment",
  "requestBooking.note": "Note",
  "requestBooking.submit": "Send request",
  "requestBooking.submitting": "Sending…",
  "requestBooking.success": "Request sent successfully.",

  "loyalty.title": "My Loyalty",
  "loyalty.points": "Points balance",
  "loyalty.tier": "Tier",
  "loyalty.loading": "Loading loyalty info…",
  "loyalty.history": "Points history",
  "loyalty.empty": "No transactions yet.",

  "loyaltyMarketing.title": "Our Loyalty Programme",
  "loyaltyMarketing.subtitle": "Earn points with every visit and unlock exclusive discounts.",
  "loyaltyMarketing.tiers.standard": "Standard",
  "loyaltyMarketing.tiers.silver": "Silver",
  "loyaltyMarketing.tiers.gold": "Gold",
  "loyaltyMarketing.cta": "Join now",

  "profile.title": "My Profile",
  "profile.name": "Name",
  "profile.email": "Email",
  "profile.phone": "Phone number",
  "profile.save": "Save changes",
  "profile.saving": "Saving…",
  "profile.saved": "Profile updated.",
  "profile.verifyEmail": "Verify email",
  "profile.verifyPhone": "Verify phone",
  "profile.verified": "Verified",

  "about.title": "About Us",
  "about.subtitle": "Meet our team and learn about our practice.",

  "contact.title": "Contact Us",
  "contact.address": "Address",
  "contact.phone": "Phone",
  "contact.email": "Email",
  "contact.hours": "Opening hours",

  "privacy.title": "Privacy Policy",
  "privacy.body": "This page will contain the full privacy policy for this dental practice, including details about what personal data is collected, how it is used, your rights under GDPR, and how to contact us with data-related requests.",
  "privacy.lastUpdated": "Last updated: coming soon. Please contact us if you have questions about your data."
}
```

### `lt.json` (Lithuanian — primary)

All keys mirror `en.json` with Lithuanian translations:

```json
{
  "nav.home": "Pagrindinis",
  "nav.doctors": "Gydytojai",
  "nav.services": "Paslaugos",
  "nav.loyalty": "Lojalumas",
  "nav.about": "Apie mus",
  "nav.contact": "Kontaktai",
  "nav.continueBooking": "Tęsti rezervaciją",
  "nav.book": "Rezervuoti",
  "nav.appointments": "Mano vizitai",
  "nav.myLoyalty": "Mano lojalumas",
  "nav.profile": "Mano profilis",
  "nav.logout": "Atsijungti",
  "nav.login": "Prisijungti",
  "nav.register": "Registruotis",

  "common.loading": "Kraunama…",
  "common.error": "Įvyko klaida.",
  "common.retry": "Bandyti dar kartą",
  "common.back": "Atgal",
  "common.save": "Išsaugoti",
  "common.saving": "Saugoma…",
  "common.cancel": "Atšaukti",
  "common.noData": "Duomenų nėra.",

  "landing.hero.title": "Jūsų šypsena — mūsų prioritetas",
  "landing.hero.subtitle": "Rezervuokite vizitą pas patyrusius odontologus greitai ir patogiai.",
  "landing.hero.cta": "Rezervuoti vizitą",
  "landing.stats.experience": "Metų patirtis",
  "landing.stats.patients": "Aptarnautų pacientų",
  "landing.stats.equipment": "Moderni įranga",
  "landing.stats.procedures": "Neskausmingas gydymas",
  "landing.steps.title": "Kaip tai veikia",
  "landing.steps.1": "Sukurkite paskyrą",
  "landing.steps.2": "Pasirinkite paslaugą ir laiką",
  "landing.steps.3": "Gaukite patvirtinimą",
  "landing.doctors.title": "Mūsų gydytojai",
  "landing.doctors.book": "Rezervuoti",
  "landing.doctors.loading": "Kraunami gydytojai…",
  "landing.services.title": "Mūsų paslaugos",
  "landing.services.viewAll": "Visos paslaugos →",
  "landing.testimonials.title": "Pacientų atsiliepimai",
  "landing.cta.title": "Pasiruošę rezervuoti?",
  "landing.cta.button": "Rezervuoti vizitą",

  "login.title": "Prisijungti",
  "login.email": "El. paštas",
  "login.password": "Slaptažodis",
  "login.submit": "Prisijungti",
  "login.submitting": "Jungiamasi…",
  "login.noAccount": "Neturite paskyros?",
  "login.register": "Registruotis",

  "register.title": "Sukurti paskyrą",
  "register.name": "Vardas",
  "register.email": "El. paštas",
  "register.phone": "Telefono numeris",
  "register.password": "Slaptažodis",
  "register.confirmPassword": "Patvirtinti slaptažodį",
  "register.consent": "Sutinku su {link} ir sutinku, kad mano asmens duomenys būtų tvarkomi.",
  "register.privacyPolicy": "Privatumo politika",
  "register.submit": "Registruotis",
  "register.submitting": "Kuriama paskyra…",
  "register.alreadyHave": "Jau turite paskyrą?",
  "register.login": "Prisijungti",

  "doctors.title": "Mūsų gydytojai",
  "doctors.book": "Rezervuoti",
  "doctors.loading": "Kraunami gydytojai…",
  "doctors.empty": "Gydytojų nerasta.",

  "slots.title": "Laisvi laikai",
  "slots.loading": "Kraunami laikai…",
  "slots.empty": "Šiam laikotarpiui laisvų laikų nėra.",
  "slots.select": "Pasirinkti laiką",
  "slots.selected": "Pasirinkta",
  "slots.continue": "Tęsti",
  "slots.selectService": "Pasirinkti paslaugą",

  "services.title": "Mūsų paslaugos",
  "services.loading": "Kraunamos paslaugos…",
  "services.empty": "Šiuo metu paslaugų nėra.",
  "services.error": "Nepavyko įkelti paslaugų.",
  "services.retry": "Bandyti dar kartą",
  "services.from": "Nuo",

  "booking.title": "Patvirtinti rezervaciją",
  "booking.doctor": "Gydytojas",
  "booking.service": "Paslauga",
  "booking.slot": "Data ir laikas",
  "booking.price": "Kaina",
  "booking.discount": "Nuolaida",
  "booking.total": "Iš viso",
  "booking.saved": "Sutaupėte €{amount}",
  "booking.loyaltyPoints": "Gausite {points} lojalumo taškų",
  "booking.confirm": "Patvirtinti rezervaciją",
  "booking.confirming": "Tvirtinama…",
  "booking.profileIncomplete": "Užpildykite profilį ir atrakinkite lojalumo nuolaidas.",
  "booking.verifyProfile": "Patvirtinti profilį",
  "booking.loadingPreview": "Skaičiuojama kaina…",

  "appointments.title": "Mano vizitai",
  "appointments.empty": "Vizitų kol kas nėra.",
  "appointments.book": "Rezervuoti vizitą",
  "appointments.status.pending": "Laukiama",
  "appointments.status.confirmed": "Patvirtinta",
  "appointments.status.cancelled": "Atšaukta",
  "appointments.status.completed": "Atlikta",
  "appointments.cancel": "Atšaukti",
  "appointments.reschedule": "Pakeisti laiką",
  "appointments.loading": "Kraunami vizitai…",

  "reschedule.title": "Pakeisti vizito laiką",
  "reschedule.submit": "Patvirtinti pakeitimą",
  "reschedule.submitting": "Keičiama…",
  "reschedule.loading": "Kraunama…",
  "reschedule.empty": "Laisvų laikų nėra.",

  "requestBooking.title": "Prašyti vizito",
  "requestBooking.note": "Pastaba",
  "requestBooking.submit": "Siųsti prašymą",
  "requestBooking.submitting": "Siunčiama…",
  "requestBooking.success": "Prašymas sėkmingai išsiųstas.",

  "loyalty.title": "Mano lojalumas",
  "loyalty.points": "Taškų likutis",
  "loyalty.tier": "Lygis",
  "loyalty.loading": "Kraunama lojalumo informacija…",
  "loyalty.history": "Taškų istorija",
  "loyalty.empty": "Sandorių dar nėra.",

  "loyaltyMarketing.title": "Lojalumo programa",
  "loyaltyMarketing.subtitle": "Kaupdami taškus kiekvieno vizito metu, atrakinkite išskirtines nuolaidas.",
  "loyaltyMarketing.tiers.standard": "Standartinis",
  "loyaltyMarketing.tiers.silver": "Sidabro",
  "loyaltyMarketing.tiers.gold": "Aukso",
  "loyaltyMarketing.cta": "Prisijungti dabar",

  "profile.title": "Mano profilis",
  "profile.name": "Vardas",
  "profile.email": "El. paštas",
  "profile.phone": "Telefono numeris",
  "profile.save": "Išsaugoti pakeitimus",
  "profile.saving": "Saugoma…",
  "profile.saved": "Profilis atnaujintas.",
  "profile.verifyEmail": "Patvirtinti el. paštą",
  "profile.verifyPhone": "Patvirtinti telefoną",
  "profile.verified": "Patvirtinta",

  "about.title": "Apie mus",
  "about.subtitle": "Susipažinkite su mūsų komanda ir klinika.",

  "contact.title": "Kontaktai",
  "contact.address": "Adresas",
  "contact.phone": "Telefonas",
  "contact.email": "El. paštas",
  "contact.hours": "Darbo laikas",

  "privacy.title": "Privatumo politika",
  "privacy.body": "Šiame puslapyje bus pateikta visa šios odontologijos klinikos privatumo politika, įskaitant informaciją apie tai, kokie asmens duomenys renkami, kaip jie naudojami, jūsų teises pagal BDAR ir kaip su mumis susisiekti dėl duomenų.",
  "privacy.lastUpdated": "Paskutinį kartą atnaujinta: netrukus. Susisiekite su mumis, jei turite klausimų dėl savo duomenų."
}
```

## Consent Checkbox Interpolation

The `register.consent` key uses a named slot for the privacy policy link. In the template, use `i18n-t` component for HTML interpolation:

```html
<i18n-t keypath="register.consent" tag="span">
  <template #link>
    <a href="/privacy" target="_blank" class="text-blue-600 hover:underline">
      {{ t('register.privacyPolicy') }}
    </a>
  </template>
</i18n-t>
```

## Testing Strategy

New file: `tests/Feature/I18nSetupTest.php` is not needed — this is a pure frontend change.

TypeScript compilation (`npm run build`) serves as the primary correctness check: if any `t()` call references a non-existent key, TypeScript will warn (with strict vue-i18n typing).

The visual/functional checks are:
1. Page loads with Lithuanian strings by default
2. Clicking "EN" switches all visible strings to English
3. Refreshing the page preserves the selected locale

## What Is Not Changing

- No backend PHP files
- No API routes or controllers
- No database migrations
- No Inertia pages (the SPA is entirely in `resources/spa/`)
- No new Vue Router routes
- API response data (doctor names, service descriptions) rendered as-is — not wrapped in `t()`
