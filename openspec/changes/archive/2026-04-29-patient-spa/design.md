# Design: Patient SPA

## Directory Structure

```
patient-spa/
├── index.html
├── package.json
├── vite.config.ts
├── tailwind.config.js
├── postcss.config.js
├── tsconfig.json
└── src/
    ├── main.ts                 — app entry, mounts Vue + installs router and pinia
    ├── App.vue                 — root component, <RouterView>
    ├── api/
    │   └── axios.ts            — shared Axios instance, base URL, Bearer interceptor
    ├── router/
    │   └── index.ts            — Vue Router routes, beforeEach auth guard
    ├── stores/
    │   ├── auth.ts             — authStore: user, token, login(), logout(), isAuthenticated
    │   └── booking.ts          — bookingStore: selectedDoctor, selectedSlot, selectedService
    ├── types/
    │   └── index.ts            — TypeScript interfaces for API shapes
    └── views/
        ├── LandingView.vue     — public: doctor cards + service list
        ├── LoginView.vue       — public: login form
        ├── RegisterView.vue    — public: register form
        ├── DoctorSlotsView.vue — auth: slot grid for :id doctor, ?date= filter
        ├── BookView.vue        — auth: confirm doctor + service + slot, submit
        ├── AppointmentsView.vue— auth: list of patient's appointments with status badges
        └── LoyaltyView.vue     — auth: points balance + tier display
```

## Scaffolding

Create as a standalone Vite project using `npm create vite@latest patient-spa -- --template vue-ts` then install additional dependencies:

```bash
npm install vue-router@4 pinia axios
npm install -D tailwindcss@3 postcss autoprefixer
npx tailwindcss init -p
```

> **Tailwind version note**: The main Laravel app uses Tailwind v4 via the `@tailwindcss/vite` plugin. The patient SPA uses Tailwind v3 with the standard PostCSS config to remain fully independent and avoid any build-tool conflicts.

## `vite.config.ts`

```ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    port: 5173,
  },
})
```

## TypeScript Interfaces (`src/types/index.ts`)

```ts
export interface Doctor {
  id: number
  name: string
  specialization: string
  bio: string
}

export interface Slot {
  id: number
  date: string        // 'YYYY-MM-DD'
  start_time: string
  end_time: string
}

export interface Service {
  id: number
  name: string
  description: string
  duration_minutes: number
  price: string
}

export interface Appointment {
  id: number
  status: string
  notes: string | null
  doctor: { id: number; name: string }
  service: { id: number; name: string }
  slot: { id: number; date: string; start_time: string }
}

export interface LoyaltyAccount {
  points_balance: number
  tier: string
}

export interface AuthUser {
  id: number
  name: string
  email: string
}
```

## Axios Instance (`src/api/axios.ts`)

```ts
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  // Read directly from localStorage to avoid a circular module dependency:
  // axios.ts → useAuthStore → api → axios.ts
  // authStore.setToken() always keeps localStorage in sync, so this is always current.
  const token = localStorage.getItem('booking_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

## Auth Store (`src/stores/auth.ts`)

```ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'
import type { AuthUser } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('booking_token'))
  const user = ref<AuthUser | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('booking_token', newToken)
  }

  async function login(email: string, password: string) {
    const { data } = await api.post('/auth/login', { email, password })
    setToken(data.token)
    user.value = data.user
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, phone?: string) {
    const { data } = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      phone,
    })
    setToken(data.token)
    user.value = data.user
  }

  async function logout() {
    await api.post('/auth/logout')
    token.value = null
    user.value = null
    localStorage.removeItem('booking_token')
  }

  return { token, user, isAuthenticated, login, register, logout }
})
```

## Booking Store (`src/stores/booking.ts`)

```ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Doctor, Slot, Service } from '@/types'

export const useBookingStore = defineStore('booking', () => {
  const selectedDoctor = ref<Doctor | null>(null)
  const selectedSlot = ref<Slot | null>(null)
  const selectedService = ref<Service | null>(null)

  function reset() {
    selectedDoctor.value = null
    selectedSlot.value = null
    selectedService.value = null
  }

  return { selectedDoctor, selectedSlot, selectedService, reset }
})
```

## Vue Router (`src/router/index.ts`)

```ts
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/',                    component: () => import('@/views/LandingView.vue') },
    { path: '/login',               component: () => import('@/views/LoginView.vue') },
    { path: '/register',            component: () => import('@/views/RegisterView.vue') },
    { path: '/doctors/:id/slots',   component: () => import('@/views/DoctorSlotsView.vue'), meta: { requiresAuth: true } },
    { path: '/book',                component: () => import('@/views/BookView.vue'),         meta: { requiresAuth: true } },
    { path: '/appointments',        component: () => import('@/views/AppointmentsView.vue'), meta: { requiresAuth: true } },
    { path: '/loyalty',             component: () => import('@/views/LoyaltyView.vue'),      meta: { requiresAuth: true } },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login' }
  }
})

export default router
```

## Entry Point (`src/main.ts`)

```ts
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
```

## View Component Responsibilities

### `LandingView.vue`
- On mount: `GET /doctors` and `GET /services` in parallel
- Renders doctor cards (name, specialization, "View Slots" link → `/doctors/:id/slots`)
- Renders service list (name, duration, price)
- Top navbar with Login / Register links (or Appointments / Logout if authenticated)

### `LoginView.vue`
- Form: email, password
- On submit: calls `authStore.login()`, on success redirect to `/appointments`
- Shows field-level validation errors from API 422 response

### `RegisterView.vue`
- Form: name, email, phone (optional), password, password confirmation
- On submit: calls `authStore.register()`, on success redirect to `/appointments`
- Shows field-level validation errors

### `DoctorSlotsView.vue`
- Route param `:id` — `GET /doctors/:id/slots` (pass `?date=` if date filter is set)
- Date input for filter, re-fetches on change
- Grid of slot cards (date, start_time–end_time); click selects a slot into `bookingStore.selectedSlot`
- Service selector (re-fetches `GET /services` if not in store); stores choice in `bookingStore.selectedService`
- "Confirm Booking" button → navigate to `/book`

### `BookView.vue`
- Reads `bookingStore.{selectedDoctor, selectedSlot, selectedService}`
- Shows summary card and "Book Now" button
- `POST /appointments` with `{ doctor_id, service_id, slot_id }`
- On success: `bookingStore.reset()`, navigate to `/appointments`
- On 422/404 error: shows inline alert

### `AppointmentsView.vue`
- `GET /appointments` on mount
- Table/card list with status badge (`pending` = yellow, `confirmed` = green, `completed` = blue, `cancelled` = grey, `no_show` = grey)
- Cancel button for `pending` / `confirmed` appointments → `DELETE /appointments/:id`, refreshes list

### `LoyaltyView.vue`
- `GET /loyalty` on mount
- Displays tier badge and points balance prominently

## Tailwind Setup

`tailwind.config.js`:
```js
/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,ts}'],
  theme: { extend: {} },
  plugins: [],
}
```

`src/style.css`:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## CORS / Sanctum

No changes needed. `config/cors.php` already allows all origins or `localhost:5173`, and the CORS middleware is applied to the `api` stack. The SPA does **not** use Sanctum cookie-based auth — it uses stateless Bearer tokens only, so no `withCredentials` or XSRF header is required.
