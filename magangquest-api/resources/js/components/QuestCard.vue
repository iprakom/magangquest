<template>
  <div class="quest-card" :class="`quest-${quest.priority}`">
    <div class="quest-header">
      <span class="quest-type-badge" :class="`type-${quest.type}`">
        {{ formatType(quest.type) }}
      </span>
      <span class="quest-difficulty-badge" :class="`difficulty-${quest.priority}`">
        {{ formatDifficulty(quest.priority) }}
      </span>
    </div>

    <h3 class="quest-title">{{ quest.title }}</h3>

    <p v-if="quest.description" class="quest-description">
      {{ truncateDescription(quest.description) }}
    </p>

    <div class="quest-meta">
      <div class="quest-xp">
        <span class="xp-icon">⚡</span>
        <span class="xp-value">{{ getSlotWeight(quest.priority) }} XP</span>
      </div>
      <div v-if="quest.due_date" class="quest-due-date">
        <span class="due-icon">📅</span>
        <span>{{ formatDate(quest.due_date) }}</span>
      </div>
    </div>

    <div v-if="userAssignment" class="quest-status" :class="`status-${userAssignment.status}`">
      <span class="status-indicator"></span>
      <span>{{ formatStatus(userAssignment.status) }}</span>
    </div>

    <div class="quest-actions">
      <button
        v-if="quest.type === 'bounty' && !userAssignment && canAccept"
        @click="$emit('accept', quest)"
        class="btn btn-accept"
        :disabled="loading"
      >
        {{ loading ? 'Claiming...' : 'Accept' }}
      </button>
      <button
        @click="$emit('view', quest)"
        class="btn btn-view"
      >
        View Details
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  quest: {
    type: Object,
    required: true,
  },
  userAssignment: {
    type: Object,
    default: null,
  },
  canAccept: {
    type: Boolean,
    default: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['accept', 'view'])

const slotWeights = {
  high: 4,
  mid: 2,
  low: 1,
}

function getSlotWeight(priority) {
  return slotWeights[priority] || 2
}

function formatType(type) {
  const types = {
    assigned: 'Assigned',
    bounty: 'Bounty',
    usulan: 'Usulan',
  }
  return types[type] || type
}

function formatDifficulty(difficulty) {
  const difficulties = {
    high: 'High',
    mid: 'Mid',
    low: 'Low',
  }
  return difficulties[difficulty] || difficulty
}

function formatStatus(status) {
  const statuses = {
    open: 'Open',
    assigned: 'Assigned',
    active: 'In Progress',
    paused: 'Paused',
    in_review: 'In Review',
    approved: 'Approved',
    revise: 'Needs Revision',
    cancelled: 'Cancelled',
    failed: 'Failed',
  }
  return statuses[status] || status
}

function formatDate(date) {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

function truncateDescription(description) {
  if (!description) return ''
  if (description.length <= 100) return description
  return description.substring(0, 100) + '...'
}
</script>

<style scoped>
.quest-card {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  border-left: 4px solid #e5e7eb;
}

.quest-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.quest-card.quest-high {
  border-left-color: #ef4444;
}

.quest-card.quest-mid {
  border-left-color: #f59e0b;
}

.quest-card.quest-low {
  border-left-color: #22c55e;
}

.quest-header {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.quest-type-badge,
.quest-difficulty-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.type-assigned {
  background: #dbeafe;
  color: #1d4ed8;
}

.type-bounty {
  background: #fef3c7;
  color: #b45309;
}

.type-usulan {
  background: #e0e7ff;
  color: #4338ca;
}

.difficulty-high {
  background: #fee2e2;
  color: #dc2626;
}

.difficulty-mid {
  background: #fef3c7;
  color: #d97706;
}

.difficulty-low {
  background: #dcfce7;
  color: #16a34a;
}

.quest-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.quest-description {
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.5;
  margin-bottom: 0.75rem;
}

.quest-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.quest-xp,
.quest-due-date {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: #4b5563;
}

.xp-icon {
  font-size: 1rem;
}

.quest-status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.75rem;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-open { background: #fef3c7; color: #b45309; }
.status-open .status-indicator { background: #f59e0b; }

.status-assigned { background: #dbeafe; color: #1d4ed8; }
.status-assigned .status-indicator { background: #3b82f6; }

.status-active { background: #dcfce7; color: #16a34a; }
.status-active .status-indicator { background: #22c55e; }

.status-paused { background: #fef3c7; color: #b45309; }
.status-paused .status-indicator { background: #f59e0b; }

.status-in_review { background: #e0e7ff; color: #4338ca; }
.status-in_review .status-indicator { background: #6366f1; }

.status-approved { background: #dcfce7; color: #16a34a; }
.status-approved .status-indicator { background: #22c55e; }

.status-revise { background: #fee2e2; color: #dc2626; }
.status-revise .status-indicator { background: #ef4444; }

.status-cancelled,
.status-failed { background: #f3f4f6; color: #6b7280; }
.status-cancelled .status-indicator,
.status-failed .status-indicator { background: #9ca3af; }

.quest-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  flex: 1;
  padding: 0.625rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  border: none;
}

.btn-accept {
  background: #22c55e;
  color: white;
}

.btn-accept:hover:not(:disabled) {
  background: #16a34a;
}

.btn-accept:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-view {
  background: #f3f4f6;
  color: #374151;
}

.btn-view:hover {
  background: #e5e7eb;
}
</style>
