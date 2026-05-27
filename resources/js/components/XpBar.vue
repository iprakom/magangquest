<script setup>
defineProps({
  xp: { type: Number, default: 0 },
  level: { type: Number, default: 1 }
})
const xpForLevel = (lvl) => lvl * 1000
const progress = (xp, lvl) => Math.min(100, ((xp % 1000) / 1000) * 100)
</script>
<template>
  <div>
    <div class="flex justify-between text-xs text-gray-400 mb-1">
      <span>Level {{ level }}</span>
      <span>{{ xp }} XP</span>
    </div>
    <div class="h-2 bg-dark-700 rounded-full overflow-hidden">
      <div
        class="h-full bg-gradient-to-r from-accent-emerald to-accent-gold transition-all duration-500"
        :style="{ width: progress(xp, level) + '%' }"
      />
    </div>
  </div>
</template>
