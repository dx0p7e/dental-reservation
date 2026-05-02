<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import { useBookingStore } from '@spa/stores/booking'
import AppNavbar from '@spa/components/AppNavbar.vue'
import DoctorAvatar from '@spa/components/DoctorAvatar.vue'
import ReviewCard from '@spa/components/ReviewCard.vue'
import type { Doctor, Review, Service } from '@spa/types'
import landingBg from '@spa/assets/landing-bg.jpg'

const { t } = useI18n()
const router = useRouter()
const bookingStore = useBookingStore()

const doctors = ref<Doctor[]>([])
const loadingDoctors = ref(true)
const services = ref<Service[]>([])
const loadingServices = ref(true)
const hasMoreServices = ref(false)
const reviews = ref<Review[]>([])
const loadingReviews = ref(true)

interface ContactForm {
  name: string
  email: string
  subject: string
  message: string
}

interface FieldErrors {
  name?: string[]
  email?: string[]
  subject?: string[]
  message?: string[]
}

const contactForm = ref<ContactForm>({ name: '', email: '', subject: '', message: '' })
const contactErrors = ref<FieldErrors>({})
const contactLoading = ref(false)
const contactSuccess = ref(false)
const contactServerError = ref('')

async function submitContactForm() {
  contactErrors.value = {}
  contactServerError.value = ''
  contactLoading.value = true
  try {
    await api.post('/contact', contactForm.value)
    contactSuccess.value = true
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { errors?: FieldErrors; message?: string } } }
    if (e.response?.status === 422) {
      contactErrors.value = e.response.data?.errors ?? {}
    } else {
      contactServerError.value = e.response?.data?.message ?? t('contact.error')
    }
  } finally {
    contactLoading.value = false
  }
}

async function fetchServices() {
  try {
    const { data } = await api.get('/services')
    const all: Service[] = data.data ?? data
    hasMoreServices.value = all.length > 6
    services.value = all.slice(0, 6)
  } catch {
    // silent fallback — landing page degrades gracefully
  } finally {
    loadingServices.value = false
  }
}

onMounted(async () => {
  fetchServices()
  try {
    const { data } = await api.get('/doctors')
    doctors.value = data.data ?? data
  } finally {
    loadingDoctors.value = false
  }
  try {
    const { data } = await api.get('/reviews')
    reviews.value = (data.data ?? data) as Review[]
  } catch {
    reviews.value = []
  } finally {
    loadingReviews.value = false
  }
})

function viewSlots(doctor: Doctor) {
  bookingStore.selectedDoctor = doctor
  router.push(`/doctors/${doctor.id}/slots`)
}

const stats = [
  { value: '10+', labelKey: 'landing.stats.experience' },
  { value: '2000+', labelKey: 'landing.stats.patients' },
  { value: 'Moderni', labelKey: 'landing.stats.equipment' },
  { value: 'Be skausmo', labelKey: 'landing.stats.procedures' },
]

const steps = [
  { num: 1, labelKey: 'landing.steps.1' },
  { num: 2, labelKey: 'landing.steps.2' },
  { num: 3, labelKey: 'landing.steps.3' },
]

const loyaltyTiers = [
  { name: 'Standard', points: 0, discount: 0, highlighted: false },
  { name: 'Silver', points: 500, discount: 10, highlighted: false },
  { name: 'Gold', points: 1500, discount: 20, highlighted: true },
]
</script>

<template>
  <div class="scroll-smooth bg-clinic-dark">
    <AppNavbar />

    <!-- Hero -->
    <section
      id="hero"
      class="relative flex min-h-screen flex-col items-center justify-center px-6 text-center"
      :style="{ backgroundImage: `url(${landingBg})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
    >
      <!-- dark overlay so text stays readable -->
      <div class="absolute inset-0 bg-clinic-dark/70" />
      <h1 class="relative z-10 max-w-2xl text-5xl font-semibold leading-tight tracking-tight text-white">
        {{ t('landing.hero.title') }}
      </h1>
      <p class="relative z-10 mt-6 max-w-xl text-lg leading-relaxed text-white/70">
        {{ t('landing.hero.subtitle') }}
      </p>
      <div class="relative z-10 mt-8 flex flex-wrap items-center justify-center gap-4">
        <RouterLink
          to="/dashboard/book"
          class="rounded-lg bg-clinic-teal px-7 py-3 text-sm font-medium text-white hover:opacity-90"
        >
          {{ t('landing.hero.cta') }}
        </RouterLink>
        <a
          href="#how-it-works"
          class="rounded-lg border border-white px-7 py-3 text-sm font-medium text-white hover:bg-white/10"
        >
          {{ t('landing.hero.learnMore') }}
        </a>
      </div>
    </section>

    <!-- Services -->
    <section id="services" class="bg-white px-6 py-20">
      <div class="mx-auto max-w-6xl">
        <h2 class="mb-10 text-center text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.services.title') }}</h2>

        <!-- Loading skeletons -->
        <div v-if="loadingServices" class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div v-for="n in 3" :key="n" class="h-24 animate-pulse rounded-lg bg-gray-200" />
        </div>

        <template v-else>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div
              v-for="svc in services"
              :key="svc.id"
              class="rounded-lg border border-clinic-border p-6"
            >
              <h3 class="font-semibold text-clinic-text">{{ svc.name }}</h3>
              <p class="mt-1 text-sm text-clinic-muted">{{ svc.duration_minutes }} min · €{{ svc.price }}</p>
              <RouterLink to="/dashboard/book" class="mt-4 inline-block text-sm text-clinic-blue hover:underline">
                {{ t('landing.services.book') }}
              </RouterLink>
            </div>
          </div>
          <div v-if="hasMoreServices" class="mt-6 text-center">
            <RouterLink to="/services" class="text-sm text-clinic-blue hover:underline">
              {{ t('landing.services.viewAll') }}
            </RouterLink>
          </div>
        </template>
      </div>
    </section>

    <!-- Why Us -->
    <section class="bg-clinic-surface py-20 px-6">
      <div class="mx-auto max-w-6xl">
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
          <div v-for="stat in stats" :key="stat.labelKey" class="text-center">
            <p class="text-3xl font-bold text-clinic-teal">{{ stat.value }}</p>
            <p class="mt-1 text-sm text-clinic-muted">{{ t(stat.labelKey) }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="bg-white py-20 px-6">
      <div class="mx-auto max-w-4xl">
        <h2 class="mb-12 text-center text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.steps.title') }}</h2>
        <div class="relative flex items-start justify-between gap-4">
          <!-- connector line -->
          <div class="absolute top-5 left-0 right-0 h-px bg-clinic-border" style="width: calc(100% - 4rem); margin: 0 2rem;" />
          <div
            v-for="step in steps"
            :key="step.num"
            class="relative z-10 flex flex-1 flex-col items-center text-center"
          >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-clinic-teal text-sm font-bold text-white">
              {{ step.num }}
            </div>
            <p class="mt-4 text-sm font-medium text-clinic-text">{{ t(step.labelKey) }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Loyalty Preview -->
    <section id="loyalty" class="bg-clinic-surface py-20 px-6">
      <div class="mx-auto max-w-5xl text-center">
        <h2 class="mb-3 text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.loyalty.title') }}</h2>
        <p class="mb-10 text-base text-clinic-muted">{{ t('landing.loyalty.subtitle') }}</p>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div
            v-for="tier in loyaltyTiers"
            :key="tier.name"
            class="rounded-xl border p-8 transition-shadow hover:shadow-md"
            :class="tier.highlighted ? 'border-clinic-teal bg-white shadow-sm' : 'border-clinic-border bg-white'"
          >
            <p
              class="mb-1 text-lg font-bold"
              :class="tier.highlighted ? 'text-clinic-teal' : 'text-clinic-text'"
            >{{ tier.name }}</p>
            <p class="text-sm text-clinic-muted">{{ tier.points }}+ {{ t('landing.loyalty.points') }}</p>
            <p class="mt-3 text-2xl font-semibold" :class="tier.highlighted ? 'text-clinic-teal' : 'text-clinic-text'">
              {{ tier.discount }}%
            </p>
            <p class="text-xs text-clinic-muted">{{ t('landing.loyalty.discountLabel') }}</p>
          </div>
        </div>
        <RouterLink to="/loyalty" class="mt-8 inline-block text-sm text-clinic-blue hover:underline">
          {{ t('landing.loyalty.learnMore') }}
        </RouterLink>
      </div>
    </section>

    <!-- About Us Preview -->
    <section id="about" class="bg-white py-20 px-6">
      <div class="mx-auto max-w-3xl text-center">
        <h2 class="mb-6 text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.about.title') }}</h2>
        <p class="leading-relaxed text-clinic-muted">{{ t('landing.about.body') }}</p>
        <RouterLink to="/about" class="mt-6 inline-block text-sm text-clinic-blue hover:underline">
          {{ t('landing.about.learnMore') }}
        </RouterLink>
      </div>
    </section>

    <!-- Doctors -->
    <section id="doctors" class="bg-clinic-surface py-20 px-6">
      <div class="mx-auto max-w-6xl">
        <h2 class="mb-10 text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.doctors.title') }}</h2>
        <div v-if="loadingDoctors" class="text-clinic-muted">{{ t('landing.doctors.loading') }}</div>
        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="doctor in doctors"
            :key="doctor.id"
            class="rounded-lg border border-clinic-border p-6"
          >
            <div class="flex flex-col items-center">
              <DoctorAvatar :photo-url="doctor.photo_url" :name="doctor.name" size="w-32 h-32" />
            </div>
            <h3 class="mt-4 font-semibold text-clinic-text">{{ doctor.name }}</h3>
            <p class="mt-1 text-sm text-clinic-blue">{{ doctor.specialization }}</p>
            <p class="mt-2 text-sm leading-relaxed text-clinic-muted line-clamp-2">{{ doctor.bio }}</p>
            <button
              @click="viewSlots(doctor)"
              class="mt-4 rounded-lg border border-clinic-blue px-4 py-1.5 text-sm text-clinic-blue hover:bg-clinic-blue hover:text-white"
            >
              {{ t('landing.doctors.book') }}
            </button>
          </div>
        </div>
        <p v-if="!loadingDoctors && doctors.length === 0" class="text-clinic-muted">{{ t('doctors.empty') }}</p>
      </div>
      <div class="mt-6 text-center">
        <RouterLink to="/doctors" class="text-sm text-clinic-blue hover:underline">
          {{ t('landing.doctors.viewMore') }}
        </RouterLink>
      </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="bg-white py-20 px-6">
      <div class="mx-auto max-w-5xl">
        <h2 class="mb-10 text-center text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.contact.title') }}</h2>
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
          <!-- Static contact info -->
          <div>
            <h3 class="text-xl font-semibold text-clinic-text">{{ t('contact.infoTitle') }}</h3>
            <p class="mt-3 text-sm text-clinic-muted">{{ t('contact.infoText') }}</p>
            <ul class="mt-6 space-y-4 text-sm text-clinic-muted">
              <li class="flex gap-3">
                <span class="font-medium text-clinic-text">{{ t('contact.address') }}:</span>
                {{ t('contact.addressValue') }}
              </li>
              <li class="flex gap-3">
                <span class="font-medium text-clinic-text">{{ t('contact.phone') }}:</span>
                {{ t('contact.phoneValue') }}
              </li>
              <li class="flex gap-3">
                <span class="font-medium text-clinic-text">{{ t('contact.email') }}:</span>
                {{ t('contact.emailValue') }}
              </li>
              <li class="flex gap-3">
                <span class="font-medium text-clinic-text">{{ t('contact.hours') }}:</span>
                {{ t('contact.hoursValue') }}
              </li>
            </ul>
          </div>

          <!-- Contact form -->
          <div>
            <div v-if="contactSuccess" class="rounded-lg border border-clinic-teal bg-teal-50 p-6 text-center text-clinic-teal">
              {{ t('contact.success') }}
            </div>
            <form v-else @submit.prevent="submitContactForm" class="space-y-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.name') }}</label>
                <input
                  v-model="contactForm.name"
                  type="text"
                  required
                  class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
                />
                <p v-if="contactErrors.name" class="mt-1 text-xs text-red-500">{{ contactErrors.name[0] }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('profile.email') }}</label>
                <input
                  v-model="contactForm.email"
                  type="email"
                  required
                  class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
                />
                <p v-if="contactErrors.email" class="mt-1 text-xs text-red-500">{{ contactErrors.email[0] }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.subject') }}</label>
                <input
                  v-model="contactForm.subject"
                  type="text"
                  required
                  class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
                />
                <p v-if="contactErrors.subject" class="mt-1 text-xs text-red-500">{{ contactErrors.subject[0] }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.message') }}</label>
                <textarea
                  v-model="contactForm.message"
                  rows="5"
                  required
                  class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
                />
                <p v-if="contactErrors.message" class="mt-1 text-xs text-red-500">{{ contactErrors.message[0] }}</p>
              </div>
              <p v-if="contactServerError" class="text-sm text-red-500">{{ contactServerError }}</p>
              <button
                type="submit"
                :disabled="contactLoading"
                class="w-full rounded-lg bg-clinic-teal px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-60"
              >
                {{ contactLoading ? t('contact.sending') : t('contact.send') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" v-if="loadingReviews || reviews.length > 0" class="bg-clinic-surface py-20 px-6">
      <div class="mx-auto max-w-6xl">
        <h2 class="mb-10 text-center text-3xl font-semibold tracking-tight text-clinic-text">{{ t('landing.testimonials.title') }}</h2>
        <!-- Loading skeletons -->
        <div v-if="loadingReviews" class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div v-for="n in 3" :key="n" class="h-48 animate-pulse rounded-lg bg-gray-200" />
        </div>
        <template v-else>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <ReviewCard v-for="review in reviews.slice(0, 3)" :key="review.id" :review="review" />
          </div>
          <div class="mt-8 text-center">
            <RouterLink to="/reviews" class="text-sm text-clinic-blue hover:underline">
              Peržiūrėti visus atsiliepimus →
            </RouterLink>
          </div>
        </template>
      </div>
    </section>

    <!-- CTA -->
    <section class="bg-white py-20 px-6 text-center">
      <h2 class="text-3xl font-semibold text-clinic-dark">{{ t('landing.cta.title') }}</h2>
      <RouterLink
        to="/dashboard/book"
        class="mt-6 inline-block rounded-lg bg-clinic-teal px-8 py-3 text-sm font-medium text-white hover:opacity-90"
      >
        {{ t('landing.cta.button') }}
      </RouterLink>
    </section>

    <!-- Footer -->
    <footer class="bg-clinic-dark text-white">
      <div class="mx-auto max-w-6xl px-6 py-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
        <div>
          <p class="text-lg font-bold">{{ t('app.clinicName') }}</p>
          <p class="mt-2 text-sm text-white/60">{{ t('footer.description') }}.</p>
        </div>
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-white/40 mb-3">{{ t('footer.navigation') }}</p>
          <ul class="space-y-2 text-sm text-white/70">
            <li><RouterLink to="/" class="hover:text-white">{{ t('footer.home') }}</RouterLink></li>
            <li><RouterLink to="/doctors" class="hover:text-white">{{ t('footer.doctors') }}</RouterLink></li>
            <li><RouterLink to="/services" class="hover:text-white">{{ t('footer.services') }}</RouterLink></li>
            <li><RouterLink to="/loyalty" class="hover:text-white">{{ t('footer.loyalty') }}</RouterLink></li>
            <li><RouterLink to="/login" class="hover:text-white">{{ t('footer.login') }}</RouterLink></li>
          </ul>
        </div>
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-white/40 mb-3">{{ t('footer.contact') }}</p>
          <ul class="space-y-2 text-sm text-white/70">
            <li>info@sypsenosklinika.lt</li>
            <li>+1 (555) 000-0000</li>
            <li>123 Sypsenos klinikos g, Vilnius</li>
          </ul>
        </div>
      </div>
      <div class="border-t border-white/10 py-4 text-center text-xs text-white/40">
        © {{ new Date().getFullYear() }} {{ t('app.clinicName') }}. {{ t('footer.rightsReserved') }}
      </div>
    </footer>
  </div>
</template>
