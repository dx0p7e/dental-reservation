## 1. Router

- [x] 1.1 Add `{ path: '/services', component: () => import('@spa/views/ServicesView.vue') }` to the routes array

## 2. AppNavbar — Services Link

- [x] 2.1 Add `{ label: 'Paslaugos', to: '/services' }` to the `publicLinks` array in `resources/spa/components/AppNavbar.vue`, positioned after the "Gydytojai" entry

## 3. ServicesView — New Page

- [x] 3.1 Create `resources/spa/views/ServicesView.vue` with `<script setup lang="ts">`
- [x] 3.2 Add the hero header section to `ServicesView.vue` template
- [x] 3.3 Add the loading skeleton state: `v-if="loading"` renders a grid with 3 `animate-pulse` placeholder cards (`bg-gray-200 rounded-xl h-48`)
- [x] 3.4 Add the error state with teal-bordered alert and Retry button
- [x] 3.5 Add the empty state: `v-else-if="services.length === 0"` renders a centred paragraph "No services are currently listed. Please contact the clinic directly."
- [x] 3.6 Add the services grid
- [x] 3.7 Wrap the whole `ServicesView.vue` template in `<AppNavbar />` at the top (import `AppNavbar` from `@spa/components/AppNavbar.vue`)

## 4. LandingView — Live Services Section

- [x] 4.1 Remove `hardcodedServices` array and unused lucide icon imports; add live services refs
- [x] 4.2 Add `fetchServices()` async function in `LandingView.vue` with silent error fallback; call in `onMounted`
- [x] 4.3 Update services section template: replace `hardcodedServices` loop with live `services`, remove icon row, format duration/price
- [x] 4.4 Add `v-if="loadingServices"` skeleton block with 3 pulse placeholders; `v-else` wraps the grid
- [x] 4.5 Add `hasMoreServices` ref and "View all services →" overflow link below the grid

## 5. Tests

- [x] 5.1 Write a Pest feature test `tests/Feature/ServicesPageTest.php`
- [x] 5.2 Run `php artisan test --compact --filter=ServicesPageTest` and confirm it passes

## 6. Formatting

- [x] 6.1 Run `vendor/bin/pint --dirty --format agent` on any modified PHP files
