<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useActiveSection } from '@spa/composables/useActiveSection'
import { useScrolled } from '@spa/composables/useScrolled'
import { useAuthStore } from '@spa/stores/auth'
import { useBookingStore } from '@spa/stores/booking'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const authStore = useAuthStore()
const bookingStore = useBookingStore()

const { scrolled } = useScrolled(1)
const { activeSection } = useActiveSection(['hero', 'services', 'loyalty', 'about', 'contact', 'doctors', 'testimonials'])

const isLanding = computed(() => route.path === '/')

const continueBookingRoute = computed(() =>
  bookingStore.bookingDraftState === 'confirmation'
    ? '/dashboard/book'
    : `/doctors/${bookingStore.selectedDoctor?.id}/slots`,
)

const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const publicLinks = [
  { labelKey: 'nav.home', to: '/', anchorId: 'hero' },
  { labelKey: 'nav.services', to: '/services', anchorId: 'services' },
  { labelKey: 'nav.loyalty', to: '/loyalty', anchorId: 'loyalty' },
  { labelKey: 'nav.about', to: '/about', anchorId: 'about' },
  { labelKey: 'nav.doctors', to: '/doctors', anchorId: 'doctors' },
  { labelKey: 'nav.contact', to: '/contact', anchorId: 'contact' },
  { labelKey: 'nav.reviews', to: '/reviews', anchorId: 'testimonials' },
]

function scrollToSection(anchorId: string) {
  const el = document.getElementById(anchorId)
  if (el) {
    const navbarHeight = 80
    const top = el.getBoundingClientRect().top + window.scrollY - navbarHeight
    window.scrollTo({ top, behavior: 'smooth' })
  }
}

function setLocale(lang: string) {
  locale.value = lang
  localStorage.setItem('locale', lang)
}

function isActive(link: { to: string; anchorId: string | null }): boolean {
  if (isLanding.value && link.anchorId !== null) {
    return activeSection.value === link.anchorId
  }

  if (link.to === '/') {
    return route.path === '/'
  }

  return route.path.startsWith(link.to)
}

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
}

function closeDropdown() {
  dropdownOpen.value = false
}

async function handleLogout() {
  closeDropdown()
  await authStore.logout()
  router.push('/')
}

function handleOutsideClick(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    dropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleOutsideClick)
})
</script>

<template>
  <nav
    class="fixed top-0 z-50 w-full transition-colors duration-300"
    :class="isLanding && !scrolled ? 'bg-transparent backdrop-blur-sm' : 'bg-clinic-dark'"
  >
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
      <!-- Logo -->
      <RouterLink to="/" class="text-2xl font-bold tracking-tight text-white">{{ t('app.clinicName') }}</RouterLink>

      <!-- Public nav links (centre) -->
      <div class="hidden items-center gap-6 md:flex">
        <template v-for="link in publicLinks" :key="link.to">
          <!-- Smooth-scroll anchor on landing page (only for links with anchorId) -->
          <button
            v-if="isLanding && link.anchorId !== null"
            @click="scrollToSection(link.anchorId)"
            :class="[
              'text-sm font-medium tracking-wide transition-colors',
              isActive(link)
                ? 'pb-0.5 text-clinic-teal'
                : 'text-white/80 hover:text-clinic-teal',
            ]"
          >
            {{ t(link.labelKey) }}
          </button>
          <!-- Router link on other pages (or landing links without anchorId) -->
          <RouterLink
            v-else
            :to="link.to"
            :class="[
              'text-sm font-medium tracking-wide transition-colors',
              isActive(link)
                ? 'pb-0.5 text-clinic-teal'
                : 'text-white/80 hover:text-clinic-teal',
            ]"
          >
            {{ t(link.labelKey) }}
          </RouterLink>
        </template>
      </div>

      <!-- Right side -->
      <div class="flex items-center gap-3">
        <!-- Language switcher -->
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

        <!-- Authenticated: username dropdown -->
        <div v-if="authStore.isAuthenticated" ref="dropdownRef" class="relative">
          <button
            @click="toggleDropdown"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-white/80 hover:bg-white/10 hover:text-white"
          >
            {{ authStore.user?.name }}
            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': dropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div
            v-if="dropdownOpen"
            class="absolute right-0 mt-2 w-48 rounded-lg border border-white/10 bg-clinic-dark py-1 shadow-lg z-99"
          >
            <RouterLink
              to="/doctors"
              @click="closeDropdown"
              class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white"
            >
              {{ t('nav.book') }}
            </RouterLink>
            <RouterLink
              to="/dashboard/appointments"
              @click="closeDropdown"
              class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white"
            >
              {{ t('nav.appointments') }}
            </RouterLink>
            <RouterLink
              to="/dashboard/loyalty"
              @click="closeDropdown"
              class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white"
            >
              {{ t('nav.myLoyalty') }}
            </RouterLink>
            <RouterLink
              to="/dashboard/profile"
              @click="closeDropdown"
              class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white"
            >
              {{ t('nav.profile') }}
            </RouterLink>
            <div class="my-1 border-t border-white/10" />
            <button
              @click="handleLogout"
              class="block w-full px-4 py-2 text-left text-sm text-white/80 hover:bg-white/10 hover:text-white"
            >
              {{ t('nav.logout') }}
            </button>
          </div>
        </div>

        <!-- Guest: Log In + Register -->
        <template v-else>
          <RouterLink to="/login" class="text-sm text-white/80 hover:text-white">{{ t('nav.login') }}</RouterLink>
          <RouterLink
            to="/register"
            class="rounded-lg bg-clinic-teal px-4 py-1.5 text-sm font-medium text-white hover:opacity-90"
          >{{ t('nav.register') }}</RouterLink>
        </template>
      </div>
    </div>

    <!-- Continue booking banner strip -->
    <div
      v-if="authStore.isAuthenticated && bookingStore.bookingDraftState !== null"
      class="relative border-t border-clinic-teal/20 bg-clinic-teal/10 py-2 text-center text-sm"
    >
      <span class="text-white/70">{{ t('nav.continueBookingBanner') }}</span>
      <RouterLink :to="continueBookingRoute" class="font-medium text-clinic-teal hover:underline">
        {{ t('nav.continueBooking') }} &rarr;
      </RouterLink>
      <button
        @click="bookingStore.clearDraft()"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-white/50 hover:text-white"
        aria-label="Dismiss"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </nav>
  <!-- Spacer so page content isn't hidden behind the fixed navbar on non-hero pages -->
  <!-- Height grows by ~36px when the banner strip is visible -->
  <div
    v-if="!isLanding"
    aria-hidden="true"
    :class="authStore.isAuthenticated && bookingStore.bookingDraftState !== null ? 'h-[108px]' : 'h-[72px]'"
  />
</template>
