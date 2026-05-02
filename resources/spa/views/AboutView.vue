<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import DoctorAvatar from '@spa/components/DoctorAvatar.vue'
import type { Doctor } from '@spa/types'

const { t } = useI18n()

const doctors = ref<Doctor[]>([])
const loadingDoctors = ref(true)
const doctorsError = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get('/doctors')
    doctors.value = data.data ?? data
  } catch {
    doctorsError.value = true
  } finally {
    loadingDoctors.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <!-- Hero -->
    <section class="bg-clinic-dark py-16 px-6 text-center">
      <h1 class="text-4xl font-semibold tracking-tight text-white">{{ t('about.title') }}</h1>
      <p class="mx-auto mt-4 max-w-xl text-lg text-white/70">
        {{ t('about.subtitle') }}
      </p>
    </section>

    <!-- Clinic info -->
    <section class="bg-white py-16 px-6">
      <div class="mx-auto max-w-3xl">
        <h2 class="text-2xl font-semibold tracking-tight text-clinic-text">{{ t('about.missionTitle') }}</h2>
        <p class="mt-4 text-clinic-muted leading-relaxed">
          {{ t('about.missionText1') }}
        </p>
        <p class="mt-4 text-clinic-muted leading-relaxed">
          {{ t('about.missionText2') }}
        </p>
      </div>
    </section>

    <!-- Team -->
    <section class="bg-clinic-surface py-16 px-6">
      <div class="mx-auto max-w-6xl">
        <h2 class="mb-10 text-3xl font-semibold tracking-tight text-clinic-text">{{ t('about.team') }}</h2>

        <div v-if="loadingDoctors" class="animate-pulse grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div v-for="i in 3" :key="i" class="h-56 rounded-lg bg-clinic-border"></div>
        </div>

        <div v-else-if="doctorsError || doctors.length === 0" class="py-8 text-center text-clinic-muted">
          {{ t('about.teamUnavailable') }}
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="doctor in doctors"
            :key="doctor.id"
            class="rounded-lg border border-clinic-border bg-white p-6"
          >
            <div class="flex flex-col items-center">
              <DoctorAvatar :photo-url="doctor.photo_url" :name="doctor.name" size="w-24 h-24" />
            </div>
            <h3 class="mt-4 text-center font-semibold text-clinic-text">{{ doctor.name }}</h3>
            <p class="mt-1 text-center text-sm text-clinic-blue">{{ doctor.specialization }}</p>
            <p v-if="doctor.bio" class="mt-2 text-sm text-clinic-muted line-clamp-3 leading-relaxed">
              {{ doctor.bio }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Technology note -->
    <section class="bg-white py-12 px-6">
      <div class="mx-auto max-w-3xl text-center">
        <p class="text-sm text-clinic-muted">
          {{ t('about.techNote') }}
        </p>
      </div>
    </section>
  </div>
</template>
