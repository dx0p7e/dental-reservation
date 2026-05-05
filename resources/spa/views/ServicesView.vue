<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import type { Service } from '@spa/types'

const { t } = useI18n()

const services = ref<Service[]>([])
const loading = ref(true)
const error = ref(false)

async function fetchServices() {
  loading.value = true
  error.value = false

  try {
    const { data } = await api.get('/services')
    services.value = data.data ?? data
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchServices()
})
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <!-- Hero header -->
    <section class="bg-clinic-dark px-6 py-16 text-center">
      <div class="mx-auto max-w-3xl">
        <h1 class="text-4xl font-semibold tracking-tight text-white">{{ t('services.title') }}</h1>
        <p class="mt-3 text-lg text-white/70">{{ t('services.subtitle', { clinic: t('app.clinicName') }) }}</p>
        <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-white/60">
          {{ t('services.description') }}
        </p>
      </div>
    </section>

    <!-- Content area -->
    <section class="mx-auto max-w-6xl px-6 py-12">
      <!-- Loading skeletons -->
      <div v-if="loading" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="n in 3"
          :key="n"
          class="h-48 animate-pulse rounded-xl bg-gray-200"
        />
      </div>

      <!-- Error state -->
      <div
        v-else-if="error"
        class="rounded-lg border border-clinic-teal p-4 text-clinic-teal"
      >
        <p>{{ t('services.error') }}</p>
        <button
          @click="fetchServices()"
          class="mt-3 rounded-lg border border-clinic-teal px-4 py-1.5 text-sm hover:bg-teal-50"
        >
          {{ t('services.retry') }}
        </button>
      </div>

      <!-- Empty state -->
      <p
        v-else-if="services.length === 0"
        class="text-center text-sm text-clinic-muted"
      >
        {{ t('services.empty') }}
      </p>

      <!-- Services grid -->
      <div
        v-else
        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="svc in services"
          :key="svc.id"
          class="rounded-xl border bg-white p-6 shadow-sm"
        >
          <h2 class="font-semibold text-clinic-text">{{ svc.name }}</h2>
          <span class="mt-2 inline-block rounded-full bg-teal-100 px-3 py-1 text-xs text-teal-800">
            {{ svc.duration_minutes }} min
          </span>
          <p class="mt-3 text-2xl font-semibold text-clinic-blue">€{{ svc.price }}</p>
          <p class="mt-3 text-sm leading-relaxed text-clinic-muted">{{ svc.description }}</p>
          <RouterLink
            to="/doctors"
            class="mt-4 inline-block text-sm text-clinic-blue hover:underline"
          >
            {{ t('doctors.book') }} →
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
