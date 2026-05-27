<script setup>
defineProps({ streak: { type: Number, default: 0 } })
</script>
<template>
  <div class="flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-bold"
    :class="streak >= 7 ? 'bg-orange-500/20 text-orange-400' : 'bg-dark-700 text-gray-400'">
    <span>🔥</span>
    <span>{{ streak }}</span>
  </div>
</template>
