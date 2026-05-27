<template>
  <div
    class="streak-badge"
    :class="{ 'streak-glow': streak >= 7, 'streak-fire': streak >= 14 }"
  >
    <span class="streak-icon">{{ streakIcon }}</span>
    <span class="streak-count">{{ streak }}</span>
    <span class="streak-label">{{ streakLabel }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  streak: {
    type: Number,
    default: 0,
  },
})

const streakIcon = computed(() => {
  if (props.streak >= 30) return '🔥🔥'
  if (props.streak >= 14) return '🔥🔥'
  if (props.streak >= 7) return '🔥'
  return '🔥'
})

const streakLabel = computed(() => {
  if (props.streak >= 30) return 'Legendary'
  if (props.streak >= 21) return 'Epic'
  if (props.streak >= 14) return 'Great'
  if (props.streak >= 7) return 'On Fire'
  if (props.streak >= 3) return 'Warming Up'
  return 'Start Streak'
})
</script>

<style scoped>
.streak-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  border: 1px solid #475569;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #f97316;
  transition: all 0.3s ease;
}

.streak-badge.streak-glow {
  border-color: #f97316;
  box-shadow: 0 0 12px rgba(249, 115, 22, 0.4);
}

.streak-badge.streak-fire {
  border-color: #ea580c;
  box-shadow: 0 0 20px rgba(249, 115, 22, 0.6);
  animation: fire-pulse 1.5s ease-in-out infinite;
}

.streak-icon {
  font-size: 1.125rem;
}

.streak-count {
  font-size: 1.125rem;
  font-weight: 700;
}

.streak-label {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}

@keyframes fire-pulse {
  0%, 100% {
    box-shadow: 0 0 20px rgba(249, 115, 22, 0.6);
  }
  50% {
    box-shadow: 0 0 30px rgba(249, 115, 22, 0.9);
  }
}
</style>