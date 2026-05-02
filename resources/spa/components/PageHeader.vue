<script setup lang="ts">
defineProps<{
  title: string
  breadcrumbs?: { label: string; to?: string }[]
}>()
</script>

<template>
  <div class="mb-6">
    <nav v-if="breadcrumbs && breadcrumbs.length" class="mb-2 flex items-center gap-1.5 text-sm text-clinic-muted">
      <template v-for="(crumb, i) in breadcrumbs" :key="i">
        <RouterLink v-if="crumb.to" :to="crumb.to" class="hover:text-clinic-blue">{{ crumb.label }}</RouterLink>
        <span v-else class="text-clinic-text">{{ crumb.label }}</span>
        <span v-if="i < breadcrumbs.length - 1" class="text-clinic-border">›</span>
      </template>
    </nav>
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-clinic-text">{{ title }}</h1>
      <div v-if="$slots.actions" class="flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
