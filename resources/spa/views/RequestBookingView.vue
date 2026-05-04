<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import type { Service } from '@spa/types'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()

const services = ref<Service[]>([])
const serviceId = ref<number | null>(null)
const preferredDate = ref('')
const notes = ref('')
const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const success = ref(false)
const profileVerified = ref(false)

const today = new Date().toISOString().split('T')[0]

const canSubmit = computed(() => !!serviceId.value && !!preferredDate.value)

onMounted(async () => {
  loading.value = true

  try {
    const [servicesRes, profileRes] = await Promise.all([
      api.get('/services'),
      api.get('/profile'),
    ])
    services.value = servicesRes.data.data ?? servicesRes.data
    profileVerified.value = !!(profileRes.data.email_verified_at && profileRes.data.phone_verified_at)

    // Pre-fill from query param
    const queryId = route.query.service_id

    if (queryId) {
      const id = Number(queryId)

      if (services.value.some(s => s.id === id)) {
        serviceId.value = id
      }
    }
  } finally {
    loading.value = false
  }
})

async function submit() {
  error.value = ''

  if (!serviceId.value || !preferredDate.value) {
    error.value = t('requestBooking.validationError')

    return
  }

  submitting.value = true

  try {
    await api.post('/appointments/request', {
      service_id: serviceId.value,
      preferred_date: preferredDate.value,
      notes: notes.value || null,
    })
    success.value = true
    setTimeout(() => router.push('/dashboard/appointments'), 1500)
  } catch (err: any) {
    error.value = err.response?.data?.message ?? t('requestBooking.error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-6xl px-6 py-10">
      <PageHeader :title="t('requestBooking.title')" />

      <div class="mx-auto max-w-lg rounded-lg border border-clinic-border bg-white p-8">
        <div v-if="success" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
          {{ t('requestBooking.success') }}
        </div>

        <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
          {{ error }}
        </div>

        <div v-if="!profileVerified" class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
          {{ t('booking.profileIncomplete') }}
          <RouterLink to="/dashboard/profile" class="ml-1 font-medium underline">{{ t('booking.verifyProfile') }} →</RouterLink>
        </div>

        <div v-if="loading" class="text-sm text-clinic-muted">{{ t('requestBooking.loadingServices') }}</div>

        <form v-else @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-clinic-text">{{ t('booking.service') }}</label>
            <select
              v-model="serviceId"
              required
              class="w-full rounded-lg border border-clinic-border bg-white px-3 py-2 text-sm text-clinic-text focus:border-clinic-teal focus:outline-none"
            >
              <option :value="null" disabled>{{ t('requestBooking.selectService') }}</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-clinic-text">{{ t('requestBooking.preferredDate') }}</label>
            <input
              v-model="preferredDate"
              type="date"
              :min="today"
              required
              class="w-full rounded-lg border border-clinic-border bg-white px-3 py-2 text-sm text-clinic-text focus:border-clinic-teal focus:outline-none"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-clinic-text">
              {{ t('requestBooking.note') }} <span class="text-clinic-muted">({{ t('common.optional') }})</span>
            </label>
            <textarea
              v-model="notes"
              rows="3"
              :placeholder="t('requestBooking.notesPlaceholder')"
              class="w-full rounded-lg border border-clinic-border bg-white px-3 py-2 text-sm text-clinic-text focus:border-clinic-teal focus:outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="submitting || !canSubmit || !profileVerified"
            class="w-full rounded-lg bg-clinic-blue py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ submitting ? t('requestBooking.submitting') : t('requestBooking.submit') }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>
