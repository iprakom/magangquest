<script setup>
const props = defineProps({ status: { type: String, default: 'pending' } })
const config = {
  pending: 'bg-gray-500/20 text-gray-400',
  in_progress: 'bg-blue-500/20 text-blue-400',
  in_review: 'bg-yellow-500/20 text-yellow-400',
  completed: 'bg-emerald-500/20 text-emerald-400',
  rejected: 'bg-red-500/20 text-red-400',
}
</script>
<template>
  <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="config[status]">
    {{ status.replace('_', ' ').toUpperCase() }}
  </span>
</template>
