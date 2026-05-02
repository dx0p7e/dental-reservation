<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import { useBookingStore } from '@spa/stores/booking'
import AppNavbar from '@spa/components/AppNavbar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import type { Doctor, Slot, Service } from '@spa/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()

const doctorId = route.params.id as string
const slots = ref<Slot[]>([])
const services = ref<Service[]>([])
const selectedDate = ref('')
const loading = ref(true)

async function fetchSlots() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (selectedDate.value) {
      params.date = selectedDate.value
    }
    const { data } = await api.get(`/doctors/${doctorId}/slots`, { params })
    slots.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

async function fetchDoctor() {
  const { data } = await api.get(`/doctors/${doctorId}`)
  bookingStore.selectedDoctor = (data.data ?? data) as Doctor
}

onMounted(async () => {
  // Draft restore: clear if rehydrated doctor doesn't match this route
  if (bookingStore.selectedDoctor !== null && bookingStore.selectedDoctor.id !== +doctorId) {
    bookingStore.clearDraft()
  }

  const tasks: Promise<unknown>[] = [
    fetchSlots(),
    api.get(`/doctors/${doctorId}/services`).then(({ data }) => {
      services.value = data.data ?? data
    }),
  ]
  if (bookingStore.selectedDoctor === null) {
    tasks.push(fetchDoctor())
  }
  await Promise.all(tasks)
})

watch(selectedDate, fetchSlots)

function selectSlot(slot: Slot) {
  bookingStore.selectedSlot = slot
}

function isSelected(slot: Slot) {
  return bookingStore.selectedSlot?.id === slot.id
}

function canConfirm() {
  return bookingStore.selectedDoctor !== null && bookingStore.selectedSlot !== null && bookingStore.selectedService !== null
}

function confirmBooking() {
  router.push('/dashboard/book')
}

const selectedService = computed(() => bookingStore.selectedService)
const discountPct = computed(() => selectedService.value?.loyalty_discount_pct ?? 0)
const promoPct = computed(() => selectedService.value?.promo_discount_pct ?? 0)
const noSlotsServiceId = computed(() => bookingStore.selectedService?.id ?? '')

const groupedSlots = computed(() => {
  const map = new Map<string, Slot[]>()
  for (const slot of slots.value) {
    const existing = map.get(slot.date)
    if (existing) {
      existing.push(slot)
    } else {
      map.set(slot.date, [slot])
    }
  }
  return map
})
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-7xl px-6 py-10">
      <PageHeader
        :title="t('slots.title')"
        :breadcrumbs="[{ label: t('nav.home'), to: '/' }, { label: t('doctors.book') }]"
      />

      <div class="grid grid-cols-[16rem_1fr] gap-8 lg:grid-cols-[16rem_1fr_16rem]">
        <!-- Left sidebar -->
        <aside class="sticky top-20 self-start">
          <!-- Service select -->
          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-clinic-text">{{ t('booking.service') }}</label>

            <p v-if="services.length === 0 && !loading" class="text-sm text-clinic-muted">
              {{ t('slots.empty') }}
            </p>

            <select
              v-else
              :value="bookingStore.selectedService?.id ?? ''"
              class="w-full rounded-lg border border-clinic-border bg-white px-3 py-2 text-sm text-clinic-text focus:border-clinic-teal focus:outline-none"
              @change="(e) => {
                const id = +(e.target as HTMLSelectElement).value
                bookingStore.selectedService = services.find(s => s.id === id) ?? null
              }"
            >
              <option value="">{{ t('slots.selectService') }}</option>
              <option v-for="service in services" :key="service.id" :value="service.id">
                {{ service.name }} ({{ service.duration_minutes }} min · {{ service.price }})
              </option>
            </select>

            <!-- Loyalty discount badge -->
            <div v-if="discountPct > 0" class="mt-2 inline-flex items-center rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-clinic-teal">
              {{ discountPct }}% {{ t('slots.memberDiscount') }}
            </div>
            <!-- Promo discount badge -->
            <div v-if="promoPct > 0" class="mt-2 inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-700">
              {{ promoPct }}% {{ t('slots.promoDiscount') }}
            </div>
          </div>

          <!-- Date filter -->
          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-clinic-text">{{ t('slots.filterByDate') }}</label>
            <input
              v-model="selectedDate"
              type="date"
              class="w-full rounded-lg border border-clinic-border bg-white px-3 py-2 text-sm text-clinic-text focus:border-clinic-teal focus:outline-none"
            />
          </div>
        </aside>

        <!-- Centre: slot grid -->
        <div class="flex-1">
          <div v-if="loading" class="text-clinic-muted">{{ t('slots.loading') }}</div>

          <template v-else>
            <!-- Empty state -->
            <div v-if="slots.length === 0" class="rounded-lg border border-clinic-border bg-white p-8 text-center text-sm text-clinic-muted">
              {{ t('slots.noSlots') }}
              <RouterLink
                :to="`/dashboard/request-appointment${noSlotsServiceId ? '?service_id=' + noSlotsServiceId : ''}`"
                class="text-clinic-blue underline"
              >
                {{ t('requestBooking.title').toLowerCase() }}
              </RouterLink>
            </div>

            <!-- Slot cards grouped by day -->
            <template v-else>
              <div v-for="[date, daySlots] in groupedSlots" :key="date" class="mt-4 first:mt-0">
                <h3 class="mb-2 text-sm font-semibold text-clinic-text">{{ date }}</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  <button
                    v-for="slot in daySlots"
                    :key="slot.id"
                    @click="selectSlot(slot)"
                    :class="[
                      'rounded-lg border p-4 text-left text-sm transition',
                      isSelected(slot)
                        ? 'border-clinic-teal bg-teal-50'
                        : 'border-clinic-border bg-white hover:border-clinic-teal',
                    ]"
                  >
                    <div class="font-medium text-clinic-text">{{ slot.date }}</div>
                    <div class="mt-1 text-clinic-muted">{{ slot.start_time }} – {{ slot.end_time }}</div>
                    <div v-if="bookingStore.selectedDoctor" class="mt-1 text-xs text-clinic-muted">
                      {{ bookingStore.selectedDoctor.name }}
                    </div>
                  </button>
                </div>
              </div>
            </template>
          </template>

          <!-- Continue button (small screens only) -->
          <div class="mt-8 lg:hidden">
            <button
              @click="confirmBooking"
              :disabled="!canConfirm()"
              class="rounded-lg bg-clinic-blue px-6 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
            >
              {{ t('slots.continue') }}
            </button>
            <p v-if="!canConfirm()" class="mt-2 text-xs text-clinic-muted">
              {{ t('slots.hint') }}
            </p>
          </div>
        </div>

        <!-- Right: sticky continue panel (large screens only) -->
        <aside class="sticky top-20 hidden self-start lg:block">
          <button
            @click="confirmBooking"
            :disabled="!canConfirm()"
            class="w-full rounded-lg bg-clinic-blue px-6 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
          >
            {{ t('slots.continue') }}
          </button>
          <p v-if="!canConfirm()" class="mt-2 text-xs text-clinic-muted">
            {{ t('slots.hint') }}
          </p>
        </aside>
      </div>
    </main>
  </div>
</template>
