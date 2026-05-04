<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@spa/api/axios'
import AppNavbar from '@spa/components/AppNavbar.vue'
import PageHeader from '@spa/components/PageHeader.vue'
import type { LoyaltyAccount } from '@spa/types'

const { t } = useI18n()

const loyalty = ref<LoyaltyAccount | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/loyalty')
    loyalty.value = data.data ?? data
    console.log('Loyalty data:', loyalty.value)
  } finally {
    loading.value = false
  }
})

const tierClasses: Record<string, string> = {
  standard: 'bg-gray-100 text-gray-600',
  silver:   'bg-slate-100 text-slate-700',
  gold:     'bg-amber-100 text-amber-700',
}

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
      <PageHeader :title="t('loyalty.title')" />

      <!-- Loading skeleton -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div class="h-8 w-32 rounded bg-clinic-border"></div>
        <div class="h-16 w-48 rounded bg-clinic-border"></div>
      </div>

      <template v-else-if="loyalty">
        <!-- Status card -->
        <div class="rounded-lg border border-clinic-border bg-white p-8 text-center">
          <span
            :class="['inline-block rounded-full px-4 py-1 text-sm font-semibold capitalize mb-4', tierClasses[loyalty.tier.toLowerCase()] ?? 'bg-gray-100 text-gray-600']"
          >
            {{ loyalty.tier }}
          </span>
          <p class="text-5xl font-bold text-clinic-text">{{ loyalty.points_balance }}</p>
          <p class="mt-2 text-clinic-muted">{{ t('loyalty.points') }}</p>

          <!-- Progress toward next tier -->
          <div v-if="loyalty.next_tier !== null" class="mt-6">
            <div class="mb-1 flex justify-between text-sm text-clinic-muted">
              <span>{{ t('loyalty.progressTo', { tier: loyalty.next_tier }) }}</span>
              <span>{{ t('loyalty.ptsToGo', { pts: loyalty.points_to_next_tier }) }}</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-clinic-border">
              <div
                class="h-2.5 rounded-full bg-clinic-teal transition-all"
                :style="{ width: progressPercent + '%' }"
              ></div>
            </div>
          </div>

          <!-- Max-tier -->
          <div v-else class="mt-6 font-semibold text-clinic-teal">
            {{ t('loyalty.maxTier') }}
          </div>
        </div>

        <!-- Transaction history -->
        <div class="mt-6 rounded-lg border border-clinic-border bg-white">
          <h2 class="border-b border-clinic-border px-6 py-4 text-base font-semibold text-clinic-text">
            {{ t('loyalty.history') }}
          </h2>

          <p v-if="(loyalty.transactions ?? []).length === 0" class="px-6 py-6 text-center text-sm text-clinic-muted">
            {{ t('loyalty.empty') }}
          </p>

          <ul v-else class="divide-y divide-clinic-border">
            <li
              v-for="tx in (loyalty.transactions ?? [])"
              :key="tx.id"
              class="flex items-center justify-between px-6 py-4"
            >
              <div>
                <p class="text-sm font-medium text-clinic-text">{{ tx.service_name ?? t('loyalty.adjustment') }}</p>
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

      <p v-else class="text-clinic-muted">{{ t('loyalty.noAccount') }}</p>
    </main>
  </div>
</template>
