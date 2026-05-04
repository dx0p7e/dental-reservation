<script setup lang="ts">
import type { Review } from '@spa/types'

defineProps<{
  review: Review
}>()

function formatDate(iso: string): string {
  const d = new Date(iso)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()

  return `${day}/${month}/${year}`
}
</script>

<template>
  <div class="rounded-lg border border-clinic-border bg-white p-6 shadow-sm">
    <div class="mb-2 flex items-center gap-1">
      <span
        v-for="star in 5"
        :key="star"
        class="text-xl"
        :class="star <= review.rating ? 'text-clinic-teal' : 'text-gray-300'"
      >★</span>
    </div>
    <p v-if="review.title" class="mb-2 font-semibold text-clinic-dark">{{ review.title }}</p>
    <p class="mb-4 text-sm text-gray-600">{{ review.body }}</p>
    <div class="flex items-center justify-between text-xs text-gray-400">
      <span class="font-medium text-clinic-dark">{{ review.patient_name }}</span>
      <span>{{ formatDate(review.created_at) }}</span>
    </div>
  </div>
</template>
