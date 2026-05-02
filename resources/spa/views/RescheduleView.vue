<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import type { Appointment, Slot } from '@spa/types'
import type { AxiosError } from 'axios'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()

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

    <main class="mx-auto max-w-4xl px-6 py-10">
      <RouterLink to="/dashboard/appointments" class="mb-6 inline-flex items-center gap-1 text-sm text-clinic-muted hover:text-clinic-text">
        ← {{ t('common.back') }}
      </RouterLink>

      <h1 class="mb-6 text-2xl font-semibold tracking-tight text-clinic-text">{{ t('reschedule.title') }}</h1>

      <div v-if="loading" class="text-sm text-clinic-muted">{{ t('reschedule.loading') }}</div>

      <template v-else-if="appointment">
        <!-- Current appointment summary -->
        <div class="mb-8 rounded-lg border border-clinic-border bg-white p-6">
          <p class="text-sm font-medium text-clinic-muted">{{ t('reschedule.current') }}</p>
          <p class="mt-1 font-semibold text-clinic-text">{{ appointment.service.name }}</p>
          <p class="mt-0.5 text-sm text-clinic-muted">{{ appointment.doctor?.name ?? t('appointments.doctorTBC') }}</p>
          <p v-if="appointment.slot" class="mt-0.5 text-sm text-clinic-muted">
            {{ appointment.slot.date }} · {{ appointment.slot.start_time.slice(0, 5) }}
          </p>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
          {{ error }}
        </div>

        <!-- Slot picker -->
        <p class="mb-3 text-sm font-medium text-clinic-text">{{ t('reschedule.pickNew') }}</p>

        <div v-if="availableSlots.length === 0" class="rounded-lg border border-clinic-border bg-white p-8 text-center text-sm text-clinic-muted">
          {{ t('reschedule.noSlots') }}
        </div>

        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          <button
            v-for="slot in availableSlots"
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

        <!-- Submit -->
        <div class="mt-8">
          <button
            @click="submit"
            :disabled="!selectedSlotId || submitting"
            class="rounded-lg bg-clinic-blue px-6 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
          >
            {{ submitting ? t('reschedule.submitting') : t('reschedule.submit') }}
          </button>
        </div>
      </template>
    </main>
  </div>
</template>
