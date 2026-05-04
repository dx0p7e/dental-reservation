<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import DoctorAvatar from '@spa/components/DoctorAvatar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import { useAuthStore } from '@spa/stores/auth'
import { useBookingStore } from '@spa/stores/booking'
import type { Doctor, Service } from '@spa/types'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const bookingStore = useBookingStore()

const doctors = ref<Doctor[]>([])
const loading = ref(true)
const selectedServiceId = ref<number | null>(null)

const uniqueServices = computed<Pick<Service, 'id' | 'name'>[]>(() => {
  const seen = new Set<number>()
  const result: Pick<Service, 'id' | 'name'>[] = []

  for (const doctor of doctors.value) {
    for (const service of doctor.services ?? []) {
      if (!seen.has(service.id)) {
        seen.add(service.id)
        result.push({ id: service.id, name: service.name })
      }
    }
  }

  return result
})

const filteredDoctors = computed<Doctor[]>(() => {
  if (selectedServiceId.value === null) {
    return doctors.value
  }

  return doctors.value.filter((d) => d.services?.some((s) => s.id === selectedServiceId.value))
})

onMounted(async () => {
  try {
    const { data } = await api.get('/doctors')
    doctors.value = data.data ?? data
  } finally {
    loading.value = false
  }
})

function viewSlots(doctor: Doctor) {
  bookingStore.clearDraft()
  bookingStore.selectedDoctor = doctor
  router.push(`/doctors/${doctor.id}/slots`)
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-6xl px-6 py-10">
      <PageHeader :title="t('doctors.title')">
        <template #description>
          {{ t('slots.selectService') }}
        </template>
      </PageHeader>

      <div v-if="loading" class="mt-10 text-sm text-clinic-muted">{{ t('doctors.loading') }}</div>

      <template v-else>
        <!-- Service chip filter (authenticated only) -->
        <div v-if="uniqueServices.length > 0" class="mt-6 flex flex-wrap gap-2">
          <button
            @click="selectedServiceId = null"
            :class="[
              'rounded-full border px-4 py-1.5 text-sm transition',
              selectedServiceId === null
                ? 'border-clinic-teal bg-clinic-teal text-white'
                : 'border-clinic-border bg-white text-clinic-text hover:border-clinic-teal',
            ]"
          >
            {{ t('common.all') }}
          </button>
          <button
            v-for="service in uniqueServices"
            :key="service.id"
            @click="selectedServiceId = service.id"
            :class="[
              'rounded-full border px-4 py-1.5 text-sm transition',
              selectedServiceId === service.id
                ? 'border-clinic-teal bg-clinic-teal text-white'
                : 'border-clinic-border bg-white text-clinic-text hover:border-clinic-teal',
            ]"
          >
            {{ service.name }}
          </button>
        </div>

        <div v-if="filteredDoctors.length === 0" class="mt-10 text-sm text-clinic-muted">
          {{ t('doctors.empty') }}
        </div>

        <div v-else class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="doctor in filteredDoctors"
            :key="doctor.id"
            class="rounded-lg border border-clinic-border bg-white p-6"
          >
            <div class="flex flex-col items-center">
              <DoctorAvatar :photo-url="doctor.photo_url" :name="doctor.name" size="w-48 h-48" />
            </div>
            <h3 class="mt-4 font-semibold text-clinic-text">{{ doctor.name }}</h3>
            <p class="mt-1 text-sm text-clinic-blue">{{ doctor.specialization }}</p>
            <p class="mt-2 text-sm leading-relaxed text-clinic-muted line-clamp-4">{{ doctor.bio }}</p>

            <!-- Service badges -->
            <div v-if="doctor.services && doctor.services.length > 0" class="mt-3 flex flex-wrap gap-1.5">
              <span
                v-for="service in doctor.services"
                :key="service.id"
                class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-clinic-teal"
              >
                {{ service.name }}
              </span>
            </div>

            <!-- CTA -->
            <button
              v-if="authStore.isAuthenticated"
              @click="viewSlots(doctor)"
              class="mt-4 rounded-lg border border-clinic-blue px-4 py-1.5 text-sm text-clinic-blue hover:bg-clinic-blue hover:text-white"
            >
              {{ t('doctors.book') }}
            </button>
            <RouterLink
              v-else
              to="/login"
              class="mt-4 inline-block rounded-lg border border-clinic-border px-4 py-1.5 text-sm text-clinic-muted hover:border-clinic-blue hover:text-clinic-blue"
            >
              {{ t('doctors.book') }}
            </RouterLink>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>
