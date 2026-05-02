<script setup lang="ts">
defineProps<{
  status: string
}>()

const colourMap: Record<string, string> = {
  pending:   'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  completed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  no_show:   'bg-gray-100 text-gray-600',
}

const labelMap: Record<string, string> = {
  pending:   'Pending',
  confirmed: 'Confirmed',
  completed: 'Completed',
  cancelled: 'Cancelled',
  no_show:   'No Show',
}

function classes(status: string): string {
  return colourMap[status] ?? 'bg-gray-100 text-gray-600'
}

function label(status: string): string {
  return labelMap[status] ?? status
}
</script>

<template>
  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', classes(status)]">
    {{ label(status) }}
  </span>
</template>
