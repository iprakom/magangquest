<template>
  <div class="nyawa-display" :class="badgeClass">
    <span class="nyawa-icon">{{ icon }}</span>
    <span class="nyawa-text">{{ displayText }}</span>
    <span v-if="isCriticalZone" class="critical-badge">🚨 FASE KRUSIAL</span>
    <span v-if="isGracePeriod" class="grace-badge">⚠️ MASA TENGGANG</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  endDate: {
    type: String,
    required: true,
  },
  holidays: {
    type: Array,
    default: () => [],
  },
  isGracePeriod: {
    type: Boolean,
    default: false,
  },
  graceStartedAt: {
    type: String,
    default: null,
  },
  gracePenalty: {
    type: Number,
    default: 0,
  },
})

/**
 * Calculate working days remaining until endDate
 * Working days = Mon-Fri, excluding holidays
 */
function calculateWorkingDaysRemaining() {
  if (!props.endDate) return null

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const end = new Date(props.endDate)
  end.setHours(0, 0, 0, 0)

  // If end date is in the past
  if (end < today) {
    return calculateWorkingDaysPast(today, end)
  }

  let count = 0
  const current = new Date(today)
  // Start from tomorrow since today doesn't count as a full working day remaining
  current.setDate(current.getDate() + 1)

  const holidayDates = props.holidays.map(h => {
    const d = new Date(h.date)
    d.setHours(0, 0, 0, 0)
    return d.getTime()
  })

  while (current <= end) {
    const dayOfWeek = current.getDay()
    const currentTime = current.getTime()

    // Check if it's a weekday (1-5 = Monday-Friday)
    const isWeekday = dayOfWeek >= 1 && dayOfWeek <= 5
    // Check if it's not a holiday
    const isNotHoliday = !holidayDates.includes(currentTime)

    if (isWeekday && isNotHoliday) {
      count++
    }
    current.setDate(current.getDate() + 1)
  }

  return count
}

/**
 * Calculate working days past since endDate
 * Returns negative value indicating days past
 */
function calculateWorkingDaysPast(today, end) {
  let count = 0
  const current = new Date(end)
  current.setDate(current.getDate() + 1) // Start from day after end

  const holidayDates = props.holidays.map(h => {
    const d = new Date(h.date)
    d.setHours(0, 0, 0, 0)
    return d.getTime()
  })

  while (current < today) {
    const dayOfWeek = current.getDay()
    const currentTime = current.getTime()

    const isWeekday = dayOfWeek >= 1 && dayOfWeek <= 5
    const isNotHoliday = !holidayDates.includes(currentTime)

    if (isWeekday && isNotHoliday) {
      count++
    }
    current.setDate(current.getDate() + 1)
  }

  return -count
}

const hValue = computed(() => {
  return calculateWorkingDaysRemaining()
})

const isCriticalZone = computed(() => {
  return hValue.value !== null && hValue.value <= 10 && hValue.value >= 0
})

const isGraduated = computed(() => {
  return hValue.value !== null && hValue.value <= 0 && !props.isGracePeriod
})

const icon = computed(() => {
  if (props.isGracePeriod) return '⚠️'
  if (isGraduated.value) return '⚫'
  if (hValue.value >= 6 && hValue.value <= 10) return '🟢'
  if (hValue.value >= 3 && hValue.value <= 5) return '🟡'
  return '🔴'
})

const badgeClass = computed(() => {
  if (props.isGracePeriod) return 'nyawa-grace'
  if (isGraduated.value) return 'nyawa-graduated'
  if (hValue.value >= 6 && hValue.value <= 10) return 'nyawa-green'
  if (hValue.value >= 3 && hValue.value <= 5) return 'nyawa-yellow'
  return 'nyawa-red'
})

const displayText = computed(() => {
  if (hValue.value === null) return '∞'
  if (props.isGracePeriod) {
    const graceDays = Math.abs(hValue.value) + 1 // H+1, H+2, etc.
    const penaltyText = props.gracePenalty > 0 ? ` - ${props.gracePenalty} poin penalty` : ''
    return `H+${graceDays}${penaltyText}`
  }
  if (isGraduated.value) return 'Graduated!'
  return `H-${hValue.value}`
})
</script>

<style scoped>
.nyawa-display {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.nyawa-icon {
  font-size: 1.25rem;
}

.nyawa-text {
  font-size: 0.875rem;
}

/* Green: H-10 to H-6 */
.nyawa-green {
  background: linear-gradient(135deg, #052e16 0%, #166534 100%);
  border: 1px solid #22c55e;
  color: #86efac;
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.3);
}

/* Yellow: H-5 to H-3 */
.nyawa-yellow {
  background: linear-gradient(135deg, #422006 0%, #a16207 100%);
  border: 1px solid #eab308;
  color: #fde047;
  box-shadow: 0 0 12px rgba(234, 179, 8, 0.3);
}

/* Red: H-2 to H-0 */
.nyawa-red {
  background: linear-gradient(135deg, #450a0a 0%, #991b1b 100%);
  border: 1px solid #ef4444;
  color: #fca5a5;
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.3);
  animation: pulse-red 1.5s ease-in-out infinite;
}

/* Critical Zone Badge */
.critical-badge {
  background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);
  border: 1px solid #fca5a5;
  color: #fef2f2;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  animation: pulse-critical 1s ease-in-out infinite;
}

/* Grace Period Badge - Orange/Amber */
.grace-badge {
  background: linear-gradient(135deg, #7c2d12 0%, #c2410c 100%);
  border: 1px solid #fdba74;
  color: #fff7ed;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  animation: pulse-grace 1.5s ease-in-out infinite;
}

/* Grace Period - Orange/Amber */
.nyawa-grace {
  background: linear-gradient(135deg, #7c2d12 0%, #c2410c 100%);
  border: 1px solid #fb923c;
  color: #fed7aa;
  box-shadow: 0 0 12px rgba(234, 88, 12, 0.4);
  animation: pulse-grace 1.5s ease-in-out infinite;
}

@keyframes pulse-grace {
  0%, 100% {
    box-shadow: 0 0 8px rgba(234, 88, 12, 0.4);
  }
  50% {
    box-shadow: 0 0 16px rgba(234, 88, 12, 0.6);
  }
}

@keyframes pulse-critical {
  0%, 100% {
    box-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
  }
  50% {
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.9);
  }
}

/* Graduated */
.nyawa-graduated {
  background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
  border: 1px solid #64748b;
  color: #e2e8f0;
  box-shadow: 0 0 12px rgba(100, 116, 139, 0.3);
}

@keyframes pulse-red {
  0%, 100% {
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.3);
  }
  50% {
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.5);
  }
}
</style>
