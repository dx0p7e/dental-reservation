<script setup lang="ts">
import type { AxiosError } from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import { useAuthStore } from '@spa/stores/auth'
import { useBookingStore } from '@spa/stores/booking'
import type { Appointment, Slot } from '@spa/types'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const bookingStore = useBookingStore()

const hasBanner = computed(() => authStore.isAuthenticated && bookingStore.bookingDraftState !== null)

const appointmentId = route.params.id as string

const appointment = ref<Appointment | null>(null)
const slots = ref<Slot[]>([])
const selectedSlotId = ref<number | null>(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')

onMounted(async () => {
  try {
    const apptRes = await api.get(`/appointments/${appointmentId}`)
    appointment.value = apptRes.data.data ?? apptRes.data
    const doctorId = appointment.value?.doctor?.id

    if (doctorId) {
      const slotsRes = await api.get(`/doctors/${doctorId}/slots`)
      slots.value = slotsRes.data.data ?? slotsRes.data
    }
  } finally {
    loading.value = false
  }
})

const availableSlots = computed(() =>
  slots.value.filter((s) => s.id !== appointment.value?.slot?.id),
)

const groupedSlots = computed(() => {
  const map = new Map<string, Slot[]>()

  for (const slot of availableSlots.value) {
    const existing = map.get(slot.date)

    if (existing) {
      existing.push(slot)
    } else {
      map.set(slot.date, [slot])
    }
  }

  return map
})

async function submit() {
  if (!selectedSlotId.value) {
    return
  }

  error.value = ''
  submitting.value = true

  try {
    await api.patch(`/appointments/${appointmentId}/reschedule`, {
      slot_id: selectedSlotId.value,
    })
    router.push('/dashboard/appointments?rescheduled=1')
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    error.value = e.response?.data?.message ?? t('reschedule.error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-7xl px-6 py-10">
      <RouterLink to="/dashboard/appointments" class="mb-6 inline-flex items-center gap-1 text-sm text-clinic-muted hover:text-clinic-text">
        ← {{ t('common.back') }}
      </RouterLink>

      <h1 class="mb-6 text-2xl font-semibold tracking-tight text-clinic-text">{{ t('reschedule.title') }}</h1>

      <div v-if="loading" class="text-sm text-clinic-muted">{{ t('reschedule.loading') }}</div>

      <template v-else-if="appointment">
        <div class="grid grid-cols-[16rem_1fr] gap-8 lg:grid-cols-[16rem_1fr_16rem]">
          <!-- Left: current appointment summary -->
          <aside :class="['sticky', hasBanner ? 'top-[116px]' : 'top-20', 'self-start']">
            <div class="rounded-lg border border-clinic-border bg-white p-6">
              <p class="text-sm font-medium text-clinic-muted">{{ t('reschedule.current') }}</p>
              <p class="mt-1 font-semibold text-clinic-text">{{ appointment.service.name }}</p>
              <p class="mt-0.5 text-sm text-clinic-muted">{{ appointment.doctor?.name ?? t('appointments.doctorTBC') }}</p>
              <p v-if="appointment.slot" class="mt-0.5 text-sm text-clinic-muted">
                {{ appointment.slot.date }} · {{ appointment.slot.start_time.slice(0, 5) }}
              </p>
              <p class="mt-3 flex items-center gap-1.5 text-xs text-amber-700">
                <span>⚠</span>
                <span>{{ t('reschedule.onceOnly') }}</span>
              </p>
            </div>
          </aside>

          <!-- Centre: slot picker grouped by date -->
          <div class="flex-1">
            <!-- Error -->
            <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
              {{ error }}
            </div>

            <p class="mb-3 text-sm font-medium text-clinic-text">{{ t('reschedule.pickNew') }}</p>

            <div v-if="availableSlots.length === 0" class="rounded-lg border border-clinic-border bg-white p-8 text-center text-sm text-clinic-muted">
              {{ t('reschedule.noSlots') }}
            </div>

            <template v-else>
              <div v-for="[date, daySlots] in groupedSlots" :key="date" class="mt-4 first:mt-0">
                <h3 class="mb-2 text-sm font-semibold text-clinic-text">{{ date }}</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  <button
                    v-for="slot in daySlots"
                    :key="slot.id"
                    @click="selectedSlotId = slot.id"
                    :class="[
                      'rounded-lg border p-4 text-left text-sm transition',
                      selectedSlotId === slot.id
                        ? 'border-clinic-teal bg-teal-50'
                        : 'border-clinic-border bg-white hover:border-clinic-teal',
                    ]"
                  >
                    <div class="font-medium text-clinic-text">{{ slot.date }}</div>
                    <div class="mt-1 text-clinic-muted">{{ slot.start_time.slice(0, 5) }} – {{ slot.end_time.slice(0, 5) }}</div>
                  </button>
                </div>
              </div>
            </template>

            <!-- Confirm button (small screens only) -->
            <div class="mt-8 lg:hidden">
              <button
                @click="submit"
                :disabled="!selectedSlotId || submitting"
                class="rounded-lg bg-clinic-blue px-6 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
              >
                {{ submitting ? t('reschedule.submitting') : t('reschedule.submit') }}
              </button>
              <p v-if="!selectedSlotId" class="mt-2 text-xs text-clinic-muted">
                {{ t('reschedule.pickNew') }}
              </p>
            </div>
          </div>

          <!-- Right: sticky confirm panel (large screens only) -->
          <aside :class="['sticky', hasBanner ? 'top-[116px]' : 'top-20', 'hidden', 'self-start', 'lg:block']">
            <button
              @click="submit"
              :disabled="!selectedSlotId || submitting"
              class="w-full rounded-lg bg-clinic-blue px-6 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
            >
              {{ submitting ? t('reschedule.submitting') : t('reschedule.submit') }}
            </button>
            <p v-if="!selectedSlotId" class="mt-2 text-xs text-clinic-muted">
              {{ t('reschedule.pickNew') }}
            </p>
          </aside>
        </div>
      </template>
    </main>
  </div>
</template>
