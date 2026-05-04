<script setup lang="ts">
import type { AxiosError } from 'axios'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@spa/stores/auth'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const loading = ref(false)

async function handleSubmit() {
  errors.value = {}
  generalError.value = ''
  loading.value = true

  try {
    await authStore.login(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard/appointments'
    router.push(redirect)
  } catch (err) {
    const e = err as AxiosError<{ errors?: Record<string, string[]>; message?: string }>

    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      generalError.value = e.response?.data?.message ?? 'Login failed. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-lg border bg-white p-8 shadow-sm">
      <h1 class="mb-6 text-2xl font-bold text-gray-900">{{ t('login.title') }}</h1>

      <div v-if="generalError" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
        {{ generalError }}
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('login.email') }}</label>
          <input
            v-model="email"
            type="email"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email[0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('login.password') }}</label>
          <input
            v-model="password"
            type="password"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded bg-clinic-teal py-2 text-sm font-medium text-white hover:bg-clinic-teal-dark disabled:opacity-50"
        >
          {{ loading ? t('login.submitting') : t('login.submit') }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        {{ t('login.noAccount') }}
        <RouterLink to="/register" class="text-clinic-teal hover:underline">{{ t('login.register') }}</RouterLink>
      </p>

      <div class="mt-4 text-center">
        <RouterLink to="/" class="text-sm text-gray-500 hover:text-clinic-teal hover:underline">{{ t('login.backToHome') }}</RouterLink>
      </div>
    </div>
  </div>
</template>
