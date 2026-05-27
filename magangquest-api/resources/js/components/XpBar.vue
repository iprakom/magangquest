<template>
  <div class="xp-bar-container">
    <div class="flex items-center justify-between mb-1">
      <span class="text-sm font-medium text-gray-300">Level {{ level }}</span>
      <span class="text-sm text-gray-400">
        {{ currentXP }} / {{ nextLevelXP }} XP
      </span>
    </div>
    <div class="xp-bar-track">
      <div
        class="xp-bar-fill"
        :class="progressClass"
        :style="{ width: `${progressPercent}%` }"
      >
        <div v-if="progressPercent > 30" class="xp-bar-glow"></div>
      </div>
    </div>
    <p class="text-xs text-gray-500 mt-1">{{ xpToNextLevel }} XP to next level</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentXP: {
    type: Number,
    default: 0,
  },
  level: {
    type: Number,
    default: 1,
  },
})

const nextLevelXP = computed(() => props.level * 100)
const previousLevelXP = computed(() => (props.level - 1) * 100)

const progressPercent = computed(() => {
  const range = nextLevelXP.value - previousLevelXP.value
  const progress = props.currentXP - previousLevelXP.value
  return Math.min(100, Math.max(0, (progress / range) * 100))
})

const xpToNextLevel = computed(() => {
  return Math.max(0, nextLevelXP.value - props.currentXP)
})

const progressClass = computed(() => {
  if (progressPercent.value >= 75) return 'xp-bar-excellent'
  if (progressPercent.value >= 50) return 'xp-bar-good'
  if (progressPercent.value >= 25) return 'xp-bar-average'
  return 'xp-bar-low'
})
</script>

<style scoped>
.xp-bar-container {
  @apply w-full;
}

.xp-bar-track {
  height: 12px;
  background: linear-gradient(to right, #1e293b, #334155);
  border-radius: 9999px;
  overflow: hidden;
  position: relative;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

.xp-bar-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.xp-bar-low {
  background: linear-gradient(90deg, #ef4444, #f87171);
}

.xp-bar-average {
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.xp-bar-good {
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
}

.xp-bar-excellent {
  background: linear-gradient(90deg, #8b5cf6, #a78bfa);
}

.xp-bar-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.3) 50%,
    transparent 100%
  );
  animation: xp-shine 2s ease-in-out infinite;
}

@keyframes xp-shine {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>