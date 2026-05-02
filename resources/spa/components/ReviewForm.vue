<script setup lang="ts">
import { ref } from 'vue'
import api from '@spa/api/axios'
import type { Review } from '@spa/types'

const emit = defineEmits<{
  submitted: [review: Review]
  cancel: []
}>()

const rating = ref(0)
const title = ref('')
const body = ref('')
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})

function setRating(value: number) {
  rating.value = value
}

async function submit() {
  errors.value = {}
  submitting.value = true
  try {
    const response = await api.post<{ data: Review }>('/reviews', {
      rating: rating.value,
      title: title.value || null,
      body: body.value,
    })
    emit('submitted', response.data.data)
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
    if (e.response?.status === 422 && e.response.data?.errors) {
      errors.value = e.response.data.errors
    } else if (e.response?.status === 409) {
      errors.value = { body: [e.response.data?.message ?? 'Jūs jau palikote atsiliepimą.'] }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-8 shadow-xl">
      <h2 class="mb-6 text-xl font-bold text-clinic-dark">Palikti atsiliepimą</h2>

      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium text-gray-700">Įvertinimas</label>
        <div class="flex gap-1">
          <button
            v-for="star in 5"
            :key="star"
            type="button"
            class="text-2xl transition-colors"
            :class="star <= rating ? 'text-clinic-teal' : 'text-gray-300 hover:text-clinic-teal'"
            @click="setRating(star)"
          >★</button>
        </div>
        <p v-if="errors.rating" class="mt-1 text-sm text-red-600">{{ errors.rating[0] }}</p>
      </div>

      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium text-gray-700">Pavadinimas (neprivalomas)</label>
        <input
          v-model="title"
          type="text"
          maxlength="150"
          placeholder="Trumpai apie savo patirtį..."
          class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-clinic-teal"
        />
        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
      </div>

      <div class="mb-6">
        <label class="mb-1 block text-sm font-medium text-gray-700">Atsiliepimas</label>
        <textarea
          v-model="body"
          rows="4"
          minlength="10"
          maxlength="1000"
          placeholder="Papasakokite apie savo patirtį..."
          class="w-full rounded-lg border border-clinic-border px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-clinic-teal"
        ></textarea>
        <p v-if="errors.body" class="mt-1 text-sm text-red-600">{{ errors.body[0] }}</p>
      </div>

      <div class="flex gap-3">
        <button
          type="button"
          :disabled="submitting"
          class="flex-1 rounded-lg bg-clinic-teal px-4 py-2 font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
          @click="submit"
        >
          {{ submitting ? 'Siunčiama...' : 'Pateikti' }}
        </button>
        <button
          type="button"
          class="flex-1 rounded-lg border border-clinic-border px-4 py-2 font-semibold text-clinic-dark transition-colors hover:bg-gray-50"
          @click="emit('cancel')"
        >
          Atšaukti
        </button>
      </div>
    </div>
  </div>
</template>
