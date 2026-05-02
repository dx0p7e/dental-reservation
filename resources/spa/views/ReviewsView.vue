<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import ReviewCard from '@spa/components/ReviewCard.vue'
import ReviewForm from '@spa/components/ReviewForm.vue'
import { useAuthStore } from '@spa/stores/auth'
import type { Review } from '@spa/types'

const authStore = useAuthStore()
const reviews = ref<Review[]>([])
const loading = ref(true)
const showForm = ref(false)
const hasReviewed = ref(false)

const canReview = computed(
  () => authStore.isAuthenticated && !hasReviewed.value,
)

async function fetchReviews() {
  loading.value = true
  try {
    const { data } = await api.get<{ data: Review[] }>('/reviews')
    reviews.value = data.data ?? (data as unknown as Review[])
  } catch {
    reviews.value = []
  } finally {
    loading.value = false
  }
}

function onSubmitted(review: Review) {
  reviews.value = [review, ...reviews.value]
  hasReviewed.value = true
  showForm.value = false
}

onMounted(() => {
  fetchReviews()
})
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <section class="bg-teal-50 px-6 py-16 text-center">
      <div class="mx-auto max-w-3xl">
        <h1 class="text-4xl font-semibold tracking-tight text-clinic-text">Atsiliepimai</h1>
        <p class="mt-3 text-lg text-clinic-muted">Ką mūsų pacientai sako apie kliniką</p>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-12">
      <!-- Action bar -->
      <div class="mb-8 flex items-center justify-end">
        <button
          v-if="canReview"
          class="rounded-lg bg-clinic-teal px-5 py-2 font-semibold text-white transition-opacity hover:opacity-90"
          @click="showForm = true"
        >
          Palikti atsiliepimą
        </button>
        <RouterLink
          v-else-if="!authStore.isAuthenticated"
          to="/login"
          class="text-sm text-clinic-teal hover:underline"
        >
          Prisijunkite, norėdami palikti atsiliepimą
        </RouterLink>
      </div>

      <!-- Loading skeletons -->
      <div v-if="loading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="n in 3" :key="n" class="h-48 animate-pulse rounded-xl bg-gray-200" />
      </div>

      <!-- Empty state -->
      <p v-else-if="reviews.length === 0" class="text-center text-sm text-clinic-muted">
        Kol kas atsiliepimų nėra. Būkite pirmi!
      </p>

      <!-- Reviews grid -->
      <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <ReviewCard v-for="review in reviews" :key="review.id" :review="review" />
      </div>
    </section>

    <!-- Review form modal -->
    <ReviewForm v-if="showForm" @submitted="onSubmitted" @cancel="showForm = false" />
  </div>
</template>
