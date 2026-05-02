<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    photoUrl: string | null
    name: string
    size?: string
  }>(),
  { size: 'w-32 h-32' },
)

const initials = computed(() => {
  return props.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0].toUpperCase())
    .join('')
})
</script>

<template>
  <img
    v-if="photoUrl"
    :src="photoUrl"
    :alt="name"
    :class="[size, 'rounded-full object-cover']"
  />
  <div
    v-else
    :class="[size, 'rounded-full bg-clinic-teal text-white flex items-center justify-center font-semibold select-none']"
  >
    {{ initials }}
  </div>
</template>
