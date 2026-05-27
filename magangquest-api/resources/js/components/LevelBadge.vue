<template>
  <div class="level-badge" :class="`level-${tier}`">
    <span class="level-icon">{{ tierIcon }}</span>
    <span class="level-number">{{ level }}</span>
    <span class="level-label">{{ tierLabel }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  level: {
    type: Number,
    default: 1,
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
})

const tier = computed(() => {
  if (props.level >= 20) return 'legendary'
  if (props.level >= 15) return 'epic'
  if (props.level >= 10) return 'rare'
  if (props.level >= 5) return 'uncommon'
  return 'common'
})

const tierLabel = computed(() => {
  const labels = {
    legendary: 'Legendary',
    epic: 'Epic',
    rare: 'Rare',
    uncommon: 'Uncommon',
    common: 'Novice',
  }
  return labels[tier.value]
})

const tierIcon = computed(() => {
  const icons = {
    legendary: '⭐',
    epic: '💎',
    rare: '🌟',
    uncommon: '✨',
    common: '🔹',
  }
  return icons[tier.value]
})
</script>

<style scoped>
.level-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-weight: 700;
  border: 2px solid;
}

.level-badge.level-common {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #38bdf8;
  color: #0369a1;
}

.level-badge.level-uncommon {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: #4ade80;
  color: #15803d;
}

.level-badge.level-rare {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
  border-color: #facc15;
  color: #a16207;
}

.level-badge.level-epic {
  background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
  border-color: #a78bfa;
  color: #7c3aed;
}

.level-badge.level-legendary {
  background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
  border-color: #fb923c;
  color: #ea580c;
  animation: legendary-glow 2s ease-in-out infinite;
}

.level-icon {
  font-size: 0.875rem;
}

.level-number {
  font-size: 1rem;
}

.level-label {
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.8;
}

@keyframes legendary-glow {
  0%, 100% {
    box-shadow: 0 0 8px rgba(251, 146, 60, 0.4);
  }
  50% {
    box-shadow: 0 0 16px rgba(251, 146, 60, 0.7);
  }
}
</style>