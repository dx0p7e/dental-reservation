<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import type { LoyaltyAccount } from '@spa/types'

const loyalty = ref<LoyaltyAccount | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/loyalty')
    loyalty.value = data.data ?? data
  } finally {
    loading.value = false
  }
})

const progressPercent = computed(() => {
  if (!loyalty.value || loyalty.value.next_tier === null || loyalty.value.points_to_next_tier === null) {
    return 100
  }

  const threshold = loyalty.value.points_balance + loyalty.value.points_to_next_tier

  return threshold > 0 ? Math.round((loyalty.value.points_balance / threshold) * 100) : 0
})
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <main class="mx-auto max-w-3xl px-6 py-10">
      <PageHeader title="Your Loyalty Status" />

      <!-- Loading skeleton -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div class="h-8 w-32 rounded bg-clinic-border"></div>
        <div class="h-16 w-48 rounded bg-clinic-border"></div>
      </div>

      <template v-else-if="loyalty">
        <!-- Status card -->
        <div class="rounded-lg border border-clinic-border bg-white p-8 text-center">
          <span
            class="inline-block rounded-full px-4 py-1 text-sm font-semibold capitalize mb-4 text-white"
            :style="{ backgroundColor: loyalty.tier_color ?? '#6b7280' }"
          >
            {{ loyalty.tier }}
          </span>
          <p class="text-5xl font-bold text-clinic-text">{{ loyalty.points_balance }}</p>
          <p class="mt-2 text-clinic-muted">points balance</p>

          <!-- Progress toward next tier -->
          <div v-if="loyalty.next_tier !== null" class="mt-6">
            <div class="mb-1 flex justify-between text-sm text-clinic-muted">
              <span>Progress to {{ loyalty.next_tier }}</span>
              <span>{{ loyalty.points_to_next_tier }} pts to go</span>
            </div>
              <div class="h-2.5 rounded-full bg-clinic-border">
              <div
                class="h-2.5 rounded-full transition-all"
                :style="{ width: progressPercent + '%', backgroundColor: loyalty.tier_color ?? '#6b7280' }"
              ></div>
            </div>
          </div>

          <!-- Max-tier -->
          <div v-else class="mt-6 font-semibold text-clinic-teal">
            Maximum tier reached
          </div>
        </div>

        <!-- Transaction history -->
        <div class="mt-6 rounded-lg border border-clinic-border bg-white">
          <h2 class="border-b border-clinic-border px-6 py-4 text-base font-semibold text-clinic-text">
            Transaction History
          </h2>

          <p v-if="(loyalty.transactions ?? []).length === 0" class="px-6 py-6 text-center text-sm text-clinic-muted">
            No transactions yet.
          </p>

          <ul v-else class="divide-y divide-clinic-border">
            <li
              v-for="tx in (loyalty.transactions ?? [])"
              :key="tx.id"
              class="flex items-center justify-between px-6 py-4"
            >
              <div>
                <p class="text-sm font-medium text-clinic-text">{{ tx.service_name ?? 'Manual adjustment' }}</p>
                <p class="mt-0.5 text-xs text-clinic-muted">{{ tx.created_at }}</p>
              </div>
              <span
                :class="['text-sm font-semibold', tx.points_delta >= 0 ? 'text-clinic-teal' : 'text-red-500']"
              >
                {{ tx.points_delta >= 0 ? '+' : '' }}{{ tx.points_delta }} pts
              </span>
            </li>
          </ul>
        </div>
      </template>

      <p v-else class="text-clinic-muted">No loyalty account found.</p>
    </main>
  </div>
</template>
