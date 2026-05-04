<script setup lang="ts">
import type { AxiosError } from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import { useBookingStore } from '@spa/stores/booking'

const { t } = useI18n()

interface PreviewData {
  service_name: string
  original_price: string
  discount_percent: number
  promo_discount_percent: number
  discount_amount: string
  final_price: string
  points_to_earn: number
  loyalty_tier: string
  loyalty_points_balance: number
}

const router = useRouter()
const bookingStore = useBookingStore()

const error = ref('')
const loading = ref(false)
const profileVerified = ref(false)

const previewData = ref<PreviewData | null>(null)
const previewLoading = ref(false)
const previewError = ref(false)

const backLink = computed(() =>
  bookingStore.selectedDoctor ? `/doctors/${bookingStore.selectedDoctor.id}/slots` : '/'
)

onMounted(async () => {
  if (!bookingStore.selectedDoctor || !bookingStore.selectedSlot || !bookingStore.selectedService) {
    router.push('/')

    return
  }

  try {
    const { data } = await api.get('/profile')
    profileVerified.value = !!(data.email_verified_at && data.phone_verified_at)
  } catch {
    // silently ignore
  }

  previewLoading.value = true

  try {
    const { data } = await api.post('/appointments/preview', {
      doctor_id: bookingStore.selectedDoctor!.id,
      service_id: bookingStore.selectedService!.id,
      slot_id: bookingStore.selectedSlot!.id,
    })
    previewData.value = data
  } catch {
    previewError.value = true
    previewData.value = null
  } finally {
    previewLoading.value = false
  }
})

const originalPrice = computed(() =>
  previewData.value
    ? parseFloat(previewData.value.original_price)
    : parseFloat(bookingStore.selectedService?.price ?? '0')
)
const finalPrice = computed(() =>
  previewData.value
    ? parseFloat(previewData.value.final_price)
    : parseFloat(bookingStore.selectedService?.price ?? '0')
)
const hasDiscount = computed(() => (previewData.value?.discount_percent ?? 0) > 0 || (previewData.value?.promo_discount_percent ?? 0) > 0)
const savedAmount = computed(() => Math.round((originalPrice.value - finalPrice.value) * 100) / 100)

function tierLabel(tier: string): string {
  const key = `loyaltyMarketing.tiers.${tier}` as const

  return t(key) !== key ? t(key) : tier
}

async function bookNow() {
  error.value = ''
  loading.value = true

  try {
    await api.post('/appointments', {
      doctor_id: bookingStore.selectedDoctor!.id,
      service_id: bookingStore.selectedService!.id,
      slot_id: bookingStore.selectedSlot!.id,
    })
    bookingStore.clearDraft()
    router.push('/dashboard/appointments')
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    error.value = e.response?.data?.message ?? 'Booking failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-6xl px-6 py-10">
      <RouterLink :to="backLink" class="mb-6 inline-flex items-center gap-1 text-sm text-clinic-muted hover:text-clinic-text">
        ← {{ t('common.back') }}
      </RouterLink>

      <div class="mx-auto max-w-lg rounded-lg border border-clinic-border bg-white p-8">
        <h1 class="mb-6 text-2xl font-semibold tracking-tight text-clinic-text">{{ t('booking.title') }}</h1>

        <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
          {{ error }}
        </div>

        <div v-if="!profileVerified" class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
          {{ t('booking.profileIncomplete') }}
          <RouterLink to="/dashboard/profile" class="ml-1 font-medium underline">{{ t('booking.verifyProfile') }} →</RouterLink>
        </div>

        <!-- Summary rows -->
        <dl class="divide-y divide-clinic-border text-sm">
          <div class="flex justify-between py-3">
            <dt class="text-clinic-muted">{{ t('booking.service') }}</dt>
            <dd class="font-medium text-clinic-text">{{ bookingStore.selectedService?.name }}</dd>
          </div>
          <div class="flex justify-between py-3">
            <dt class="text-clinic-muted">{{ t('booking.doctor') }}</dt>
            <dd class="font-medium text-clinic-text">{{ bookingStore.selectedDoctor?.name }}</dd>
          </div>
          <div class="flex justify-between py-3">
            <dt class="text-clinic-muted">{{ t('booking.slot') }}</dt>
            <dd class="font-medium text-clinic-text">{{ bookingStore.selectedSlot?.date }}</dd>
          </div>
          <div class="flex justify-between py-3">
            <dt class="text-clinic-muted">{{ t('booking.slot') }}</dt>
            <dd class="font-medium text-clinic-text">
              {{ bookingStore.selectedSlot?.start_time }} – {{ bookingStore.selectedSlot?.end_time }}
            </dd>
          </div>

          <!-- Price row -->
          <div class="flex justify-between py-3">
            <dt class="text-clinic-muted">{{ t('booking.price') }}</dt>
            <dd class="font-medium text-clinic-text">
              <!-- Loading skeleton -->
              <template v-if="previewLoading">
                <span class="inline-block h-4 w-20 animate-pulse rounded bg-clinic-border"></span>
              </template>
              <!-- Loaded with discount -->
              <template v-else-if="previewData && hasDiscount">
                <span class="mr-1 text-clinic-muted line-through">€{{ originalPrice.toFixed(2) }}</span>
                <span class="text-clinic-teal">€{{ finalPrice.toFixed(2) }}</span>
              </template>
              <!-- Loaded, no discount -->
              <template v-else-if="previewData">
                €{{ finalPrice.toFixed(2) }}
              </template>
              <!-- Error fallback -->
              <template v-else>
                €{{ originalPrice.toFixed(2) }}
                <span class="ml-1 text-xs text-clinic-muted">({{ t('booking.loadingPreview') }})</span>
              </template>
            </dd>
          </div>

          <!-- Tier discount row -->
          <div v-if="previewData && previewData.discount_percent > 0" class="flex justify-between py-2">
            <dt class="text-clinic-muted">{{ t('booking.discount') }}</dt>
            <dd class="text-clinic-teal">
              -{{ previewData.discount_percent }}%
              <span v-if="previewData.loyalty_tier !== 'standard'" class="ml-1">
                ({{ tierLabel(previewData.loyalty_tier) }} {{ t('loyalty.tier').toLowerCase() }})
              </span>
            </dd>
          </div>

          <!-- Promo discount row -->
          <div v-if="previewData && previewData.promo_discount_percent > 0" class="flex justify-between py-2">
            <dt class="text-clinic-muted">{{ t('booking.promoDiscount') }}</dt>
            <dd class="text-orange-600">
              -{{ previewData.promo_discount_percent }}%
            </dd>
          </div>

          <!-- Points preview -->
          <div v-if="previewData" class="flex justify-between py-2">
            <dt class="text-clinic-muted">{{ t('loyalty.points') }}</dt>
            <dd class="text-clinic-teal">
              +{{ previewData.points_to_earn }} tšk.
              <span class="text-clinic-muted">({{ t('loyalty.points').toLowerCase() }}: {{ previewData.loyalty_points_balance + previewData.points_to_earn }} tšk.)</span>
            </dd>
          </div>

          <!-- Tier label (non-standard only) -->
          <div v-if="previewData && previewData.loyalty_tier !== 'standard'" class="flex justify-between py-2">
            <dt class="text-clinic-muted">{{ t('loyalty.tier') }}</dt>
            <dd class="font-medium text-clinic-text">{{ tierLabel(previewData.loyalty_tier) }}</dd>
          </div>
        </dl>

        <p v-if="previewData && hasDiscount" class="mt-1 text-sm text-clinic-teal">
          {{ t('booking.saved', { amount: savedAmount.toFixed(2) }) }}
        </p>

        <button
          @click="bookNow"
          :disabled="loading || !profileVerified || previewLoading"
          class="mt-8 w-full rounded-lg bg-clinic-blue py-3 text-sm font-medium text-white disabled:opacity-50"
        >
          {{ loading ? t('booking.confirming') : t('booking.confirm') }}
        </button>
      </div>
    </main>
  </div>
</template>
