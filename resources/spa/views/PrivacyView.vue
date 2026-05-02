<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-50">
    <div class="mx-auto w-full max-w-3xl px-4 py-16">
      <h1 class="mb-6 text-3xl font-bold text-gray-900">{{ t('privacy.title') }}</h1>

      <p class="mb-4 text-gray-700">
        {{ t('privacy.body') }}
      </p>

      <p class="text-sm text-gray-500">
        {{ t('privacy.lastUpdated') }}
      </p>
    </div>
  </div>
</template>
