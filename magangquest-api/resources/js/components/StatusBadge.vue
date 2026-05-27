<template>
  <span class="status-badge" :class="`status-${status}`">
    <span class="status-indicator"></span>
    <span class="status-text">{{ displayText }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
})

const statusConfig = {
  open: { text: 'Open', class: 'open' },
  assigned: { text: 'Assigned', class: 'assigned' },
  active: { text: 'In Progress', class: 'active' },
  paused: { text: 'Paused', class: 'paused' },
  in_review: { text: 'In Review', class: 'in-review' },
  approved: { text: 'Completed', class: 'approved' },
  revise: { text: 'Needs Revision', class: 'revise' },
  cancelled: { text: 'Cancelled', class: 'cancelled' },
  failed: { text: 'Failed', class: 'failed' },
}

const displayText = computed(() => {
  return statusConfig[props.status]?.text || props.status
})
</script>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* Open */
.status-open {
  background: #fef3c7;
  color: #b45309;
}
.status-open .status-indicator {
  background: #f59e0b;
  box-shadow: 0 0 6px #f59e0b;
}

/* Assigned */
.status-assigned {
  background: #dbeafe;
  color: #1d4ed8;
}
.status-assigned .status-indicator {
  background: #3b82f6;
  box-shadow: 0 0 6px #3b82f6;
}

/* Active/In Progress */
.status-active {
  background: #dcfce7;
  color: #166534;
}
.status-active .status-indicator {
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
  animation: pulse 2s ease-in-out infinite;
}

/* Paused */
.status-paused {
  background: #fef3c7;
  color: #b45309;
}
.status-paused .status-indicator {
  background: #f59e0b;
}

/* In Review */
.status-in-review {
  background: #e0e7ff;
  color: #4338ca;
}
.status-in-review .status-indicator {
  background: #6366f1;
  box-shadow: 0 0 6px #6366f1;
  animation: pulse 1.5s ease-in-out infinite;
}

/* Approved/Completed */
.status-approved {
  background: #dcfce7;
  color: #166534;
}
.status-approved .status-indicator {
  background: #22c55e;
}

/* Revise */
.status-revise {
  background: #fee2e2;
  color: #991b1b;
}
.status-revise .status-indicator {
  background: #ef4444;
  box-shadow: 0 0 6px #ef4444;
}

/* Cancelled */
.status-cancelled {
  background: #f3f4f6;
  color: #6b7280;
}
.status-cancelled .status-indicator {
  background: #9ca3af;
}

/* Failed */
.status-failed {
  background: #fee2e2;
  color: #991b1b;
}
.status-failed .status-indicator {
  background: #ef4444;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>