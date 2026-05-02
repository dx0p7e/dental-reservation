<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import type { AxiosError } from 'axios'

const { t } = useI18n()

const route = useRoute()

// Flash messages from query params
const emailVerifiedNotice = ref(route.query.verified === '1')
const invalidLinkError = ref(route.query.error === 'invalid_link')

// Profile data
const name = ref('')
const email = ref('')
const phone = ref('')
const notificationChannel = ref('email')
const emailVerifiedAt = ref<string | null>(null)
const phoneVerifiedAt = ref<string | null>(null)

const profileSuccess = ref('')
const profileError = ref('')
const profileLoading = ref(false)

// Phone OTP
const otpCode = ref('')
const otpSent = ref(false)
const otpLoading = ref(false)
const otpVerifyLoading = ref(false)
const otpError = ref('')
const otpSuccess = ref('')
const countdown = ref(0)
let countdownInterval: ReturnType<typeof setInterval> | null = null

// Password change
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordSuccess = ref('')
const passwordError = ref('')
const passwordLoading = ref(false)

async function fetchProfile() {
  try {
    const { data } = await api.get('/profile')
    name.value = data.name ?? ''
    email.value = data.email ?? ''
    phone.value = data.phone ?? ''
    notificationChannel.value = data.notification_channel ?? 'email'
    emailVerifiedAt.value = data.email_verified_at ?? null
    phoneVerifiedAt.value = data.phone_verified_at ?? null
  } catch {
    // silently ignore
  }
}

onMounted(fetchProfile)

async function saveProfile() {
  profileSuccess.value = ''
  profileError.value = ''
  profileLoading.value = true
  try {
    await api.patch('/profile', {
      name: name.value,
      email: email.value,
      phone: phone.value || null,
      notification_channel: notificationChannel.value,
    })
    profileSuccess.value = t('profile.saved')
    await fetchProfile()
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    profileError.value = e.response?.data?.message ?? t('profile.saveError')
  } finally {
    profileLoading.value = false
  }
}

async function resendEmailVerification() {
  await api.post('/email/verification-notification')
  profileSuccess.value = t('profile.verifyEmail')
}

function startCountdown() {
  countdown.value = 600
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
  countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--
    } else {
      clearInterval(countdownInterval!)
    }
  }, 1000)
}

function formatCountdown(seconds: number): string {
  const m = Math.floor(seconds / 60).toString().padStart(2, '0')
  const s = (seconds % 60).toString().padStart(2, '0')
  return `${m}:${s}`
}

async function sendOtp() {
  otpError.value = ''
  otpLoading.value = true
  try {
    await api.post('/phone/send-otp')
    otpSent.value = true
    startCountdown()
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    otpError.value = e.response?.data?.message ?? t('profile.otpSendError')
  } finally {
    otpLoading.value = false
  }
}

async function verifyOtp() {
  otpError.value = ''
  otpVerifyLoading.value = true
  try {
    await api.post('/phone/verify-otp', { code: otpCode.value })
    otpSuccess.value = t('profile.phoneVerifiedSuccess')
    if (countdownInterval) {
      clearInterval(countdownInterval)
    }
    await fetchProfile()
  } catch (err) {
    const e = err as AxiosError<{ message?: string }>
    otpError.value = e.response?.data?.message ?? t('profile.otpCodeError')
  } finally {
    otpVerifyLoading.value = false
  }
}

async function changePassword() {
  passwordSuccess.value = ''
  passwordError.value = ''
  passwordLoading.value = true
  try {
    await api.patch('/profile/password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    passwordSuccess.value = t('profile.passwordChanged')
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err) {
    const e = err as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
    if (e.response?.data?.errors) {
      const firstError = Object.values(e.response.data.errors)[0]
      passwordError.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else {
      passwordError.value = e.response?.data?.message ?? t('profile.saveError')
    }
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-2xl px-6 py-10">
      <h1 class="mb-8 text-2xl font-bold text-clinic-dark">{{ t('profile.title') }}</h1>

      <!-- Flash messages -->
      <div v-if="emailVerifiedNotice" class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
        {{ t('profile.emailVerifiedNotice') }}
      </div>
      <div v-if="invalidLinkError" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
        {{ t('profile.invalidLink') }}
      </div>

      <!-- Personal details -->
      <section class="mb-8 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-clinic-dark">{{ t('profile.personalDetails') }}</h2>

        <div v-if="profileSuccess" class="mb-3 rounded-lg bg-green-50 p-3 text-sm text-green-800">
          {{ profileSuccess }}
        </div>
        <div v-if="profileError" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-800">
          {{ profileError }}
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.name') }}</label>
          <input
            v-model="name"
            type="text"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.email') }}</label>
          <div class="flex items-center gap-2">
            <input
              :value="email"
              type="email"
              readonly
              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm"
            />
            <button
              v-if="!emailVerifiedAt"
              @click="resendEmailVerification"
              class="shrink-0 rounded-lg bg-clinic-teal px-3 py-1.5 text-xs font-medium text-white hover:bg-opacity-90"
            >
              {{ t('profile.verifyEmail') }}
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.phone') }}</label>
          <input
            v-model="phone"
            type="text"
            placeholder="+370..."
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
        </div>

        <div class="mb-6">
          <label class="mb-2 block text-sm font-medium text-clinic-muted">{{ t('profile.notifications') }}</label>
          <div class="flex gap-4">
            <label class="flex cursor-pointer items-center gap-2 text-sm">
              <input v-model="notificationChannel" type="radio" value="email" class="accent-clinic-teal" />
              {{ t('profile.notifEmail') }}
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm">
              <input v-model="notificationChannel" type="radio" value="sms" class="accent-clinic-teal" />
              {{ t('profile.notifSms') }}
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm">
              <input v-model="notificationChannel" type="radio" value="both" class="accent-clinic-teal" />
              {{ t('profile.notifBoth') }}
            </label>
          </div>
        </div>

        <button
          @click="saveProfile"
          :disabled="profileLoading"
          class="rounded-lg bg-clinic-teal px-5 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50"
        >
          {{ profileLoading ? t('profile.saving') : t('profile.save') }}
        </button>
      </section>

      <!-- Phone verification -->
      <section v-if="phone && !phoneVerifiedAt" class="mb-8 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-clinic-dark">{{ t('profile.phoneVerification') }}</h2>

        <div v-if="otpSuccess" class="mb-3 rounded-lg bg-green-50 p-3 text-sm text-green-800">
          {{ otpSuccess }}
        </div>
        <div v-if="otpError" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-800">
          {{ otpError }}
        </div>

        <p class="mb-4 text-sm text-clinic-muted">
          {{ t('profile.otpSent', { phone }) }}
        </p>

        <button
          @click="sendOtp"
          :disabled="otpLoading"
          class="mb-4 rounded-lg bg-clinic-teal px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50"
        >
          {{ otpLoading ? t('profile.sendingCode') : t('profile.sendCode') }}
        </button>

        <div v-if="otpSent" class="space-y-3">
          <div class="flex items-center gap-2">
            <input
              v-model="otpCode"
              type="text"
              maxlength="6"
              :placeholder="t('profile.otpPlaceholder')"
              class="w-32 rounded-lg border border-gray-200 px-3 py-2 text-center text-sm tracking-widest focus:border-clinic-teal focus:outline-none"
            />
            <button
              @click="verifyOtp"
              :disabled="otpVerifyLoading || otpCode.length !== 6"
              class="rounded-lg bg-clinic-dark px-4 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50"
            >
              {{ otpVerifyLoading ? t('profile.verifyingCode') : t('profile.verifyCode') }}
            </button>
            <span v-if="countdown > 0" class="text-sm text-clinic-muted">
              {{ formatCountdown(countdown) }}
            </span>
          </div>
        </div>
      </section>

      <div v-else-if="phone && phoneVerifiedAt" class="mb-8 rounded-xl bg-green-50 p-4">
        <p class="text-sm font-medium text-green-800">✓ {{ t('profile.phoneVerified', { phone }) }}</p>
      </div>

      <div v-if="emailVerifiedAt" class="mb-8 rounded-xl bg-green-50 p-4">
        <p class="text-sm font-medium text-green-800">✓ {{ t('profile.emailVerified', { email }) }}</p>
      </div>

      <!-- Password change -->
      <section class="rounded-xl bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-clinic-dark">{{ t('profile.changePassword') }}</h2>

        <div v-if="passwordSuccess" class="mb-3 rounded-lg bg-green-50 p-3 text-sm text-green-800">
          {{ passwordSuccess }}
        </div>
        <div v-if="passwordError" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-800">
          {{ passwordError }}
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.currentPassword') }}</label>
          <input
            v-model="currentPassword"
            type="password"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.newPassword') }}</label>
          <input
            v-model="newPassword"
            type="password"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
        </div>
        <div class="mb-6">
          <label class="mb-1 block text-sm font-medium text-clinic-muted">{{ t('profile.confirmPassword') }}</label>
          <input
            v-model="confirmPassword"
            type="password"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-clinic-teal focus:outline-none"
          />
        </div>

        <button
          @click="changePassword"
          :disabled="passwordLoading"
          class="rounded-lg bg-clinic-teal px-5 py-2 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-50"
        >
          {{ passwordLoading ? t('profile.passwordChanging') : t('profile.passwordChangeBtn') }}
        </button>
      </section>
    </main>
  </div>
</template>
