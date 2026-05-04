<script setup lang="ts">
import type { AxiosError } from 'axios'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import StatusBadge from '@spa/components/StatusBadge.vue'
import { useAuthStore } from '@spa/stores/auth'
import type { Appointment } from '@spa/types'

const { t } = useI18n()

const route = useRoute()
const authStore = useAuthStore()

const appointments = ref<Appointment[]>([])
const loading = ref(true)
const cancelError = ref('')
const cancelling = ref<number | null>(null)
const successMessage = ref('')

async function fetchAppointments() {
  loading.value = true

  try {
    const { data } = await api.get('/appointments')
    appointments.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (route.query.rescheduled === '1') {
    successMessage.value = t('appointments.rescheduled')
  }

  fetchAppointments()
})

async function cancel(id: number) {
  cancelError.value = ''
  cancelling.value = id

  try {
    await api.delete(`/appointments/${id}`)
    await fetchAppointments()
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    cancelError.value = e.response?.data?.message ?? t('appointments.cancelError')
  } finally {
    cancelling.value = null
  }
}

function canCancel(status: string) {
  return status === 'pending' || status === 'confirmed'
}

function canReschedule(status: string, hasSlot: boolean, rescheduledAt: string | null): boolean {
  return (status === 'pending' || status === 'confirmed') && hasSlot && rescheduledAt === null
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-6xl px-6 py-10">
      <PageHeader :title="t('appointments.title')">
        <template #actions>
          <RouterLink
            to="/dashboard/request-appointment"
            class="rounded-lg border border-clinic-border px-4 py-2 text-sm font-medium text-clinic-text hover:bg-clinic-border"
          >
            {{ t('appointments.request') }}
          </RouterLink>
          <RouterLink
            to="/doctors"
            class="rounded-lg bg-clinic-blue px-4 py-2 text-sm font-medium text-white"
          >
            {{ t('appointments.book') }}
          </RouterLink>
        </template>
      </PageHeader>

      <div v-if="loading" class="mt-6 text-sm text-clinic-muted">{{ t('appointments.loading') }}</div>

      <template v-else>
        <!-- Success banner -->
        <div v-if="successMessage" class="mt-4 flex items-center justify-between rounded-lg bg-teal-50 px-4 py-3 text-sm text-teal-800">
          <span>{{ successMessage }}</span>
          <button @click="successMessage = ''" class="ml-4 text-teal-600 hover:text-teal-800">✕</button>
        </div>
        <!-- Cancel error -->
        <div v-if="cancelError" class="mt-4 flex items-center justify-between rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
          <span>{{ cancelError }}</span>
          <button @click="cancelError = ''" class="ml-4 text-red-500 hover:text-red-700">✕</button>
        </div>
        <!-- Empty state -->
        <div v-if="appointments.length === 0" class="mt-6 rounded-lg border border-clinic-border bg-white p-12 text-center">
          <p class="text-clinic-muted">{{ t('appointments.empty') }}</p>
          <RouterLink to="/doctors" class="mt-2 inline-block text-sm text-clinic-blue hover:underline">
            {{ t('appointments.book') }}
          </RouterLink>
        </div>

        <!-- Appointment cards -->
        <div v-else class="mt-6 space-y-3">
          <div
            v-for="appt in appointments"
            :key="appt.id"
            class="rounded-lg border border-clinic-border bg-white p-5"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-clinic-text">{{ appt.doctor?.name ?? t('appointments.doctorTBC') }}</p>
                <p class="mt-0.5 text-sm text-clinic-muted">{{ appt.service.name }}</p>
                <p class="mt-0.5 text-sm text-clinic-muted">
                  <template v-if="appt.slot">{{ appt.slot.date }} {{ appt.slot.start_time }}</template>
                  <template v-else-if="appt.preferred_date">{{ t('appointments.preferred', { date: appt.preferred_date }) }}</template>
                  <template v-else>{{ t('appointments.awaitingConfirmation') }}</template>
                </p>
                <p v-if="appt.final_price !== null" class="mt-0.5 text-sm">
                  <template v-if="appt.discount_pct > 0">
                    <span class="mr-1 text-clinic-muted line-through">€{{ appt.service.price }}</span>
                    <span class="font-medium text-clinic-teal">€{{ appt.final_price }}</span>
                  </template>
                  <template v-else>
                    <span class="text-clinic-muted">€{{ appt.final_price }}</span>
                  </template>
                </p>
              </div>
              <div class="flex items-center gap-3">
                <StatusBadge :status="appt.status" />
                <RouterLink
                  v-if="canReschedule(appt.status, appt.slot !== null, appt.rescheduled_at)"
                  :to="`/dashboard/appointments/${appt.id}/reschedule`"
                  class="rounded-lg border border-clinic-border px-3 py-1 text-xs text-clinic-text hover:bg-clinic-surface"
                >
                  {{ t('appointments.reschedule') }}
                </RouterLink>
                <button
                  v-if="canCancel(appt.status)"
                  @click="cancel(appt.id)"
                  :disabled="cancelling === appt.id"
                  class="rounded-lg border border-red-200 px-3 py-1 text-xs text-red-600 hover:bg-red-50 disabled:opacity-50"
                >
                  {{ cancelling === appt.id ? t('appointments.cancelling') : t('appointments.cancel') }}
                </button>
              </div>
            </div>
            <div
              v-if="appt.status === 'pending' && !authStore.user?.smart_id_verified_at"
              class="mt-3 flex items-center gap-2 rounded-md bg-yellow-50 px-3 py-2 text-xs text-yellow-800"
            >
              <span>⚠</span>
              <span>{{ t('appointments.pendingSmartIdHint') }}</span>
              <RouterLink to="/dashboard/profile" class="ml-auto shrink-0 font-medium underline hover:text-yellow-900">
                Smart-ID
              </RouterLink>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>
