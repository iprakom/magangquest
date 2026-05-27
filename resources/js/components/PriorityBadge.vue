<script setup>
const props = defineProps({ priority: { type: String, default: 'medium' } })
const config = { high: 'bg-red-500/20 text-red-400', medium: 'bg-yellow-500/20 text-yellow-400', low: 'bg-green-500/20 text-green-400' }
</script>
<template>
  <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="config[priority]">
    {{ priority.toUpperCase() }}
  </span>
</template>
