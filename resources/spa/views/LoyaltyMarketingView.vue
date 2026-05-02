<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@spa/stores/auth'
import AppNavbar from '@spa/components/AppNavbar.vue'

const { t } = useI18n()
const authStore = useAuthStore()

const ctaLink = computed(() => (authStore.isAuthenticated ? '/dashboard/loyalty' : '/register'))

const tiers = [
  { key: 'standard', points: 0, discount: 0, classes: 'border-gray-200 bg-gray-50', badgeClasses: 'bg-gray-100 text-gray-600' },
  { key: 'silver', points: 500, discount: 10, classes: 'border-slate-300 bg-slate-50', badgeClasses: 'bg-slate-100 text-slate-700' },
  { key: 'gold', points: 1500, discount: 20, classes: 'border-amber-300 bg-amber-50', badgeClasses: 'bg-amber-100 text-amber-700' },
]

const steps = [
  { num: 1, labelKey: 'loyaltyMarketing.step1.label', descKey: 'loyaltyMarketing.step1.desc' },
  { num: 2, labelKey: 'loyaltyMarketing.step2.label', descKey: 'loyaltyMarketing.step2.desc' },
  { num: 3, labelKey: 'loyaltyMarketing.step3.label', descKey: 'loyaltyMarketing.step3.desc' },
]
</script>

<template>
  <div class="min-h-screen bg-clinic-surface">
    <AppNavbar />

    <!-- Hero -->
    <section class="bg-clinic-dark py-20 px-6 text-center">
      <h1 class="text-4xl font-semibold tracking-tight text-white">
        {{ t('loyaltyMarketing.title') }}
      </h1>
      <p class="mx-auto mt-4 max-w-xl text-lg text-white/70">
        {{ t('loyaltyMarketing.subtitle') }}
      </p>
      <RouterLink
        :to="ctaLink"
        class="mt-8 inline-block rounded-lg bg-clinic-teal px-8 py-3 text-sm font-medium text-white hover:opacity-90"
      >
        {{ authStore.isAuthenticated ? t('loyaltyMarketing.ctaAuth') : t('loyaltyMarketing.cta') }}
      </RouterLink>
    </section>

    <!-- How it works -->
    <section class="bg-white py-20 px-6">
      <div class="mx-auto max-w-4xl">
        <h2 class="mb-12 text-center text-3xl font-semibold tracking-tight text-clinic-text">
          {{ t('loyaltyMarketing.howTitle') }}
        </h2>
        <div class="relative flex items-start justify-between gap-4">
          <div class="absolute top-5 left-0 right-0 h-px bg-clinic-border" style="width: calc(100% - 4rem); margin: 0 2rem;" />
          <div
            v-for="step in steps"
            :key="step.num"
            class="relative z-10 flex flex-1 flex-col items-center text-center"
          >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-clinic-teal text-sm font-bold text-white">
              {{ step.num }}
            </div>
            <p class="mt-4 text-sm font-semibold text-clinic-text">{{ t(step.labelKey) }}</p>
            <p class="mt-1 text-xs text-clinic-muted">{{ t(step.descKey) }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Tier cards -->
    <section class="bg-clinic-surface py-20 px-6">
      <div class="mx-auto max-w-4xl">
        <h2 class="mb-10 text-center text-3xl font-semibold tracking-tight text-clinic-text">
          {{ t('loyaltyMarketing.tiersTitle') }}
        </h2>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div
            v-for="tier in tiers"
            :key="tier.name"
            :class="['rounded-lg border-2 p-6 text-center', tier.classes]"
          >
            <span :class="['inline-block rounded-full px-4 py-1 text-sm font-semibold', tier.badgeClasses]">
              {{ t(`loyaltyMarketing.tiers.${tier.key}`) }}
            </span>
            <p class="mt-4 text-2xl font-bold text-clinic-text">
              {{ tier.points === 0 ? t('loyaltyMarketing.standard.points') : t('loyaltyMarketing.tier.points', { points: tier.points }) }}
            </p>
            <p class="mt-2 text-sm text-clinic-muted">
              {{ tier.discount === 0 ? t('loyaltyMarketing.standard.benefit') : t('loyaltyMarketing.tier.discount', { discount: tier.discount }) }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA bottom -->
    <section class="bg-clinic-dark py-16 px-6 text-center">
      <h2 class="text-2xl font-semibold text-white">{{ t('loyaltyMarketing.ctaBottom') }}</h2>
      <RouterLink
        :to="ctaLink"
        class="mt-6 inline-block rounded-lg bg-clinic-teal px-8 py-3 text-sm font-medium text-white hover:opacity-90"
      >
        {{ authStore.isAuthenticated ? t('loyaltyMarketing.ctaAuth') : t('loyaltyMarketing.cta') }}
      </RouterLink>
    </section>
  </div>
</template>
