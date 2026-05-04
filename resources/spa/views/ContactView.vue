<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'

const { t } = useI18n()

interface ContactForm {
  name: string
  email: string
  subject: string
  message: string
}

interface FieldErrors {
  name?: string[]
  email?: string[]
  subject?: string[]
  message?: string[]
}

const form = ref<ContactForm>({ name: '', email: '', subject: '', message: '' })
const errors = ref<FieldErrors>({})
const submitting = ref(false)
const submitted = ref(false)
const serverError = ref('')

async function submit() {
  errors.value = {}
  serverError.value = ''
  submitting.value = true

  try {
    await api.post('/contact', form.value)
    submitted.value = true
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { errors?: FieldErrors; message?: string } } }

    if (e.response?.status === 422) {
      errors.value = e.response.data?.errors ?? {}
    } else {
      serverError.value = e.response?.data?.message ?? t('contact.error')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <!-- Hero -->
    <section class="bg-clinic-dark py-16 px-6 text-center">
      <h1 class="text-4xl font-semibold tracking-tight text-white">{{ t('contact.title') }}</h1>
      <p class="mx-auto mt-4 max-w-xl text-lg text-white/70">
        {{ t('contact.subtitle') }}
      </p>
    </section>

    <main class="mx-auto max-w-5xl px-6 py-16 grid grid-cols-1 gap-12 lg:grid-cols-2">
      <!-- Static contact info -->
      <div>
        <h2 class="text-xl font-semibold text-clinic-text">{{ t('contact.infoTitle') }}</h2>
        <p class="mt-3 text-sm text-clinic-muted">
          {{ t('contact.infoText') }}
        </p>
        <ul class="mt-6 space-y-4 text-sm text-clinic-muted">
          <li class="flex gap-3">
            <span class="font-medium text-clinic-text">{{ t('contact.address') }}:</span>
            {{ t('contact.addressValue') }}
          </li>
          <li class="flex gap-3">
            <span class="font-medium text-clinic-text">{{ t('contact.phone') }}:</span>
            {{ t('contact.phoneValue') }}
          </li>
          <li class="flex gap-3">
            <span class="font-medium text-clinic-text">{{ t('contact.email') }}:</span>
            {{ t('contact.emailValue') }}
          </li>
          <li class="flex gap-3">
            <span class="font-medium text-clinic-text">{{ t('contact.hours') }}:</span>
            {{ t('contact.hoursValue') }}
          </li>
        </ul>
      </div>

      <!-- Contact form -->
      <div>
        <!-- Success state -->
        <div v-if="submitted" class="rounded-lg border border-clinic-teal bg-teal-50 p-6 text-center text-clinic-teal">
          {{ t('contact.success') }}
        </div>

        <form v-else @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.name') }}</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
            />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('profile.email') }}</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.subject') }}</label>
            <input
              v-model="form.subject"
              type="text"
              required
              class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
            />
            <p v-if="errors.subject" class="mt-1 text-xs text-red-500">{{ errors.subject[0] }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-clinic-text">{{ t('contact.message') }}</label>
            <textarea
              v-model="form.message"
              required
              rows="5"
              class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:border-clinic-teal focus:outline-none"
            ></textarea>
            <p v-if="errors.message" class="mt-1 text-xs text-red-500">{{ errors.message[0] }}</p>
          </div>

          <p v-if="serverError" class="text-sm text-red-500">{{ serverError }}</p>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-clinic-teal px-4 py-2.5 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50"
          >
            {{ submitting ? 'Siunčiama…' : 'Siųsti' }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>
