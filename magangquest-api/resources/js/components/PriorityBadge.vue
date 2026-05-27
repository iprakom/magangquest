<template>
  <span class="priority-badge" :class="`priority-${priority}`">
    <span class="priority-dot"></span>
    <span class="priority-text">{{ displayText }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  priority: {
    type: String,
    required: true,
    validator: (value) => ['high', 'mid', 'low'].includes(value),
  },
  showLabel: {
    type: Boolean,
    default: true,
  },
})

const displayText = computed(() => {
  const labels = {
    high: 'High',
    mid: 'Mid',
    low: 'Low',
  }
  return props.showLabel ? labels[props.priority] || props.priority : ''
})
</script>

<style scoped>
.priority-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.priority-high {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  color: #991b1b;
  border: 1px solid #fecaca;
}

.priority-mid {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
  color: #92400e;
  border: 1px solid #fde68a;
}

.priority-low {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  color: #166534;
  border: 1px solid #bbf7d0;
}

.priority-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.priority-high .priority-dot {
  background: #ef4444;
  box-shadow: 0 0 4px #ef4444;
}

.priority-mid .priority-dot {
  background: #f59e0b;
  box-shadow: 0 0 4px #f59e0b;
}

.priority-low .priority-dot {
  background: #22c55e;
  box-shadow: 0 0 4px #22c55e;
}
</style>