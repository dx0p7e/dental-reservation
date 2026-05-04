<script setup lang="ts">
import type { AxiosError } from 'axios'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@spa/stores/auth'

const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const gdprConsent = ref(false)
const errors = ref<Record<string, string[]>>({})
const loading = ref(false)

async function handleSubmit() {
  errors.value = {}
  loading.value = true

  try {
    await authStore.register(
      name.value,
      email.value,
      password.value,
      passwordConfirmation.value,
      phone.value || undefined,
      gdprConsent.value,
    )
    router.push('/dashboard/appointments')
  } catch (err) {
    const e = err as AxiosError<{ errors?: Record<string, string[]> }>

    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-lg border bg-white p-8 shadow-sm">
      <h1 class="mb-6 text-2xl font-bold text-gray-900">{{ t('register.title') }}</h1>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('register.name') }}</label>
          <input
            v-model="name"
            type="text"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('register.email') }}</label>
          <input
            v-model="email"
            type="email"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email[0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('register.phone') }}</label>
          <input
            v-model="phone"
            type="tel"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
          <p v-if="errors.phone" class="mt-1 text-xs text-red-600">{{ errors.phone[0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('register.password') }}</label>
          <input
            v-model="password"
            type="password"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
          <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ t('register.confirmPassword') }}</label>
          <input
            v-model="passwordConfirmation"
            type="password"
            required
            class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
            <input
              v-model="gdprConsent"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600"
            />
            <i18n-t keypath="register.consent" tag="span">
              <template #link>
                <a href="/privacy" target="_blank" class="text-clinic-teal hover:underline">{{ t('register.privacyPolicy') }}</a>
              </template>
            </i18n-t>
          </label>
          <p v-if="errors.gdpr_consent" class="mt-1 text-xs text-red-600">{{ errors.gdpr_consent[0] }}</p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded bg-clinic-teal py-2 text-sm font-medium text-white hover:bg-clinic-teal-dark disabled:opacity-50"
        >
          {{ loading ? t('register.submitting') : t('register.submit') }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        {{ t('register.alreadyHave') }}
        <RouterLink to="/login" class="text-clinic-teal hover:underline">{{ t('register.login') }}</RouterLink>
      </p>

      <div class="mt-4 text-center">
        <RouterLink to="/" class="text-sm text-gray-500 hover:text-clinic-teal hover:underline">{{ t('login.backToHome') }}</RouterLink>
      </div>
    </div>
  </div>
</template>
