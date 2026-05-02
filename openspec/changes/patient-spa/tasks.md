# Tasks: Patient SPA

## T1 — Scaffold the Vite + Vue TS project

- [x] Run `npm create vite@latest patient-spa -- --template vue-ts` from the project root
- [x] `cd patient-spa && npm install`
- [x] Install runtime deps: `npm install vue-router@4 pinia axios`
- [x] Install Tailwind CSS v3 dev deps: `npm install -D tailwindcss@3 postcss autoprefixer`
- [x] Run `npx tailwindcss init -p` to generate `tailwind.config.js` and `postcss.config.js`
- [x] Verify `npm run dev` starts on `localhost:5173` with the default Vite+Vue page

## T2 — Configure Vite and TypeScript

- [x] Replace `patient-spa/vite.config.ts` with the design spec version (port 5173, `@` alias)
- [x] Update `patient-spa/tsconfig.json` to include path alias `"@/*": ["src/*"]`
- [x] Update `patient-spa/tailwind.config.js` content array to `['./index.html', './src/**/*.{vue,ts}']`
- [x] Replace `patient-spa/src/style.css` with `@tailwind base/components/utilities` directives
- [x] Import `./style.css` in `src/main.ts` (replacing default `./assets/vue.svg` etc. imports)

## T3 — TypeScript types

- [x] Create `src/types/index.ts` with interfaces: `Doctor`, `Slot`, `Service`, `Appointment`, `LoyaltyAccount`, `AuthUser`

## T4 — Axios instance

- [x] Create `src/api/axios.ts` with base URL `http://localhost:8000/api/v1`, `Accept: application/json` header
- [x] Add request interceptor to attach `Authorization: Bearer {token}` from `authStore.token` when present

## T5 — Pinia stores

- [x] Create `src/stores/auth.ts` — `useAuthStore` with `token` (hydrated from `localStorage.getItem('booking_token')`), `user`, `isAuthenticated`, `login()`, `register()`, `logout()`
- [x] Create `src/stores/booking.ts` — `useBookingStore` with `selectedDoctor`, `selectedSlot`, `selectedService`, `reset()`

## T6 — Vue Router with auth guard

- [x] Create `src/router/index.ts` with all 7 routes; mark `/doctors/:id/slots`, `/book`, `/appointments`, `/loyalty` with `meta: { requiresAuth: true }`
- [x] Add `beforeEach` guard: redirect to `/login` when `requiresAuth && !auth.isAuthenticated`

## T7 — Entry point and App.vue

- [x] Update `src/main.ts` to create Pinia, install router, mount app
- [x] Replace `src/App.vue` with a single-root shell containing `<RouterView />`

## T8 — LandingView

- [x] Create `src/views/LandingView.vue`
- [x] On `onMounted`: parallel `GET /doctors` and `GET /services` via Axios
- [x] Render doctor cards (name, specialization, "View Slots" → `/doctors/:id/slots`)
- [x] Render service rows (name, duration, price)
- [x] Shared navbar: links to `/login`, `/register` (guest) or `/appointments`, logout button (authed)

## T9 — LoginView

- [x] Create `src/views/LoginView.vue`
- [x] Form with email and password fields
- [x] On submit: call `authStore.login(email, password)`, redirect to `/appointments` on success
- [x] Display 422 validation errors and 401/403 messages inline

## T10 — RegisterView

- [x] Create `src/views/RegisterView.vue`
- [x] Form with name, email, phone (optional), password, password_confirmation
- [x] On submit: call `authStore.register(...)`, redirect to `/appointments` on success
- [x] Display 422 field-level errors inline

## T11 — DoctorSlotsView

- [x] Create `src/views/DoctorSlotsView.vue`
- [x] Read `:id` from `useRoute().params`
- [x] `GET /doctors/:id/slots` on mount and on date change (pass `?date=` when set)
- [x] Date `<input type="date">` filter
- [x] Grid of slot cards; clicking a slot sets `bookingStore.selectedSlot` and the doctor into `bookingStore.selectedDoctor`
- [x] Service `<select>` populated from `GET /services`; selection stored in `bookingStore.selectedService`
- [x] "Confirm Booking" button navigates to `/book` (disabled until both slot and service are chosen)

## T12 — BookView

- [x] Create `src/views/BookView.vue`
- [x] Read `bookingStore.{selectedDoctor, selectedSlot, selectedService}`; redirect to `/` if any are null
- [x] Summary card showing doctor name, service, slot date/time
- [x] "Book Now" button: `POST /appointments` with `{ doctor_id, service_id, slot_id }`
- [x] On success: `bookingStore.reset()`, navigate to `/appointments`
- [x] On 422/404 error: show inline alert message

## T13 — AppointmentsView

- [x] Create `src/views/AppointmentsView.vue`
- [x] `GET /appointments` on mount
- [x] Render list/cards with status badge (`pending`=yellow, `confirmed`=green, `completed`=blue, `cancelled`=grey)
- [x] Cancel button for `pending`/`confirmed` → `DELETE /appointments/:id`, refresh list on success
- [x] Empty state message when no appointments

## T14 — LoyaltyView

- [x] Create `src/views/LoyaltyView.vue`
- [x] `GET /loyalty` on mount
- [x] Display tier badge and points balance
- [x] Loading skeleton while fetching

## T15 — Smoke test (manual)

- [ ] Run `npm run dev` inside `patient-spa/`
- [ ] Visit `http://localhost:5173/` — confirm doctor cards and service list load
- [ ] Register a new patient account, confirm redirect to `/appointments`
- [ ] Log out, log back in
- [ ] Pick a doctor → select slot + service → book → confirm appointment appears in list
- [ ] Cancel the appointment, confirm status changes to `cancelled`
- [ ] Visit `/loyalty` — confirm points balance displays

## T16 — Pint (PHP formatter)

- [x] Run `vendor/bin/pint --dirty --format agent` from the project root to ensure any PHP files touched during this change are correctly formatted (likely none, but run as a safety check)
