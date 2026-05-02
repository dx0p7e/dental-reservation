import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@spa/api/axios'
import type { AuthUser } from '@spa/types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('booking_token'))
  const user = ref<AuthUser | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('booking_token', newToken)
  }

  async function login(email: string, password: string) {
    const { data } = await api.post('/auth/login', { email, password })
    setToken(data.token)
    user.value = data.user
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, phone?: string, gdprConsent?: boolean) {
    const { data } = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      phone,
      gdpr_consent: gdprConsent,
    })
    setToken(data.token)
    user.value = data.user
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await api.get('auth/user', { baseURL: '/api' })
      user.value = data.user
    } catch {
      token.value = null
      localStorage.removeItem('booking_token')
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('booking_token')
    }
  }

  return { token, user, isAuthenticated, login, register, logout, fetchUser }
})
