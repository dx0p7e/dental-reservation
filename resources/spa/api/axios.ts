import axios from 'axios'

const api = axios.create({
  baseURL: (import.meta.env.VITE_API_URL as string | undefined) ?? '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  // Read directly from localStorage to avoid a circular module dependency:
  // axios.ts → useAuthStore → api → axios.ts
  // authStore.setToken() always keeps localStorage in sync, so this is always current.
  const token = localStorage.getItem('booking_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export default api
