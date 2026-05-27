<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Quest Board</h1>
        <p class="mt-1 text-gray-600">Browse and claim quests to earn XP</p>
      </div>

      <!-- WIP Slots Status -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">📊</span>
            <div>
              <p class="text-sm font-medium text-gray-700">WIP Slots</p>
              <p class="text-xs text-gray-500">{{ wipSlots.used }} / {{ wipSlots.max }} used</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="slotUtilizationClass"
                :style="{ width: `${Math.min(wipSlots.utilization_percent, 100)}%` }"
              ></div>
            </div>
            <span class="text-sm font-medium" :class="slotUtilizationTextClass">
              {{ wipSlots.utilization_percent }}%
            </span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select
              v-model="filters.type"
              @change="applyFilters"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All Types</option>
              <option value="assigned">Assigned</option>
              <option value="bounty">Bounty</option>
              <option value="usulan">Usulan</option>
            </select>
          </div>

          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
            <select
              v-model="filters.difficulty"
              @change="applyFilters"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All Difficulties</option>
              <option value="high">High</option>
              <option value="mid">Mid</option>
              <option value="low">Low</option>
            </select>
          </div>

          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="filters.is_active"
              @change="applyFilters"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option :value="true">Active Only</option>
              <option :value="false">Inactive Only</option>
              <option value="">All</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button
              @click="clearFilters"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
            >
              Clear
            </button>
            <button
              v-if="$page.props.auth?.user?.role === 'admin'"
              @click="showCreateModal = true"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              + Create Quest
            </button>
          </div>
        </div>
      </div>

      <!-- Quest Grid -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-800">{{ error }}</p>
        <button @click="fetchQuests" class="mt-2 text-sm text-red-600 hover:text-red-800">
          Try Again
        </button>
      </div>

      <div v-else-if="quests.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
        <span class="text-6xl mb-4 block">🎯</span>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No quests found</h3>
        <p class="text-gray-600">Try adjusting your filters or check back later.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <QuestCard
          v-for="quest in quests"
          :key="quest.id"
          :quest="quest"
          :user-assignment="getUserAssignment(quest.id)"
          :can-accept="canAcceptQuest(quest)"
          :loading="acceptingQuestId === quest.id"
          @accept="handleAccept"
          @view="handleViewDetails"
        />
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
        <button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 rounded-md"
          :class="pagination.current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
        >
          Previous
        </button>

        <button
          v-for="page in visiblePages"
          :key="page"
          @click="goToPage(page)"
          class="px-4 py-2 rounded-md"
          :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
        >
          {{ page }}
        </button>

        <button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 rounded-md"
          :class="pagination.current_page === pagination.last_page ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Create Quest Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Create New Quest</h2>

          <form @submit.prevent="handleCreateQuest">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <input
                v-model="newQuest.title"
                type="text"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="newQuest.description"
                rows="3"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  v-model="newQuest.type"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="bounty">Bounty</option>
                  <option value="assigned">Assigned</option>
                  <option value="usulan">Usulan</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
                <select
                  v-model="newQuest.priority"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="low">Low</option>
                  <option value="mid">Mid</option>
                  <option value="high">High</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input
                  v-model="newQuest.start_date"
                  type="date"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input
                  v-model="newQuest.due_date"
                  type="date"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="creating"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {{ creating ? 'Creating...' : 'Create Quest' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Quest Details Modal -->
    <div v-if="showDetailsModal && selectedQuest" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <span class="quest-type-badge" :class="`type-${selectedQuest.type}`">
                {{ formatType(selectedQuest.type) }}
              </span>
              <span class="quest-difficulty-badge ml-2" :class="`difficulty-${selectedQuest.priority}`">
                {{ formatDifficulty(selectedQuest.priority) }}
              </span>
            </div>
            <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
              <span class="text-2xl">&times;</span>
            </button>
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-2">{{ selectedQuest.title }}</h2>

          <p v-if="selectedQuest.description" class="text-gray-600 mb-4">
            {{ selectedQuest.description }}
          </p>

          <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
            <div>
              <span class="text-gray-500">XP Reward:</span>
              <span class="ml-2 font-medium">{{ getSlotWeight(selectedQuest.priority) }} XP</span>
            </div>
            <div v-if="selectedQuest.due_date">
              <span class="text-gray-500">Due Date:</span>
              <span class="ml-2 font-medium">{{ formatDate(selectedQuest.due_date) }}</span>
            </div>
            <div v-if="selectedQuest.creator">
              <span class="text-gray-500">Created By:</span>
              <span class="ml-2 font-medium">{{ selectedQuest.creator.name }}</span>
            </div>
            <div>
              <span class="text-gray-500">Status:</span>
              <span class="ml-2 font-medium">{{ selectedQuest.is_active ? 'Active' : 'Inactive' }}</span>
            </div>
          </div>

          <div v-if="selectedQuestUserAssignment" class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-700 mb-1">Your Assignment</p>
            <p class="text-sm" :class="`status-${selectedQuestUserAssignment.status}`">
              Status: {{ formatStatus(selectedQuestUserAssignment.status) }}
            </p>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              v-if="selectedQuest.type === 'bounty' && !selectedQuestUserAssignment && canAcceptQuest(selectedQuest)"
              @click="handleAccept(selectedQuest); showDetailsModal = false"
              :disabled="acceptingQuestId === selectedQuest.id"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
            >
              {{ acceptingQuestId === selectedQuest.id ? 'Accepting...' : 'Accept Quest' }}
            </button>
            <button
              v-if="$page.props.auth?.user?.role === 'admin'"
              @click="handleDelete(selectedQuest.id); showDetailsModal = false"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            >
              Delete
            </button>
            <button
              @click="showDetailsModal = false"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuestStore } from '@/stores/quest'
import QuestCard from '@/components/QuestCard.vue'

const questStore = useQuestStore()

const quests = computed(() => questStore.quests)
const loading = computed(() => questStore.loading)
const error = computed(() => questStore.error)
const pagination = computed(() => questStore.pagination)
const wipSlots = computed(() => questStore.wipSlots)

const filters = ref({
  type: '',
  difficulty: '',
  is_active: true,
})

const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const selectedQuest = ref(null)
const selectedQuestUserAssignment = ref(null)
const acceptingQuestId = ref(null)
const creating = ref(false)

const newQuest = ref({
  title: '',
  description: '',
  type: 'bounty',
  priority: 'mid',
  start_date: '',
  due_date: '',
})

const userAssignments = computed(() => questStore.assignments)

const slotUtilizationClass = computed(() => {
  const percent = wipSlots.value.utilization_percent
  if (percent >= 100) return 'bg-red-500'
  if (percent >= 75) return 'bg-yellow-500'
  if (percent >= 50) return 'bg-blue-500'
  return 'bg-green-500'
})

const slotUtilizationTextClass = computed(() => {
  const percent = wipSlots.value.utilization_percent
  if (percent >= 100) return 'text-red-600'
  if (percent >= 75) return 'text-yellow-600'
  if (percent >= 50) return 'text-blue-600'
  return 'text-green-600'
})

const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []

  let start = Math.max(1, current - 2)
  let end = Math.min(last, start + 4)

  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

onMounted(async () => {
  await Promise.all([
    questStore.fetchQuests(),
    questStore.fetchAssignments(),
    questStore.fetchWipSlots(),
  ])
})

function applyFilters() {
  questStore.fetchQuests(filters.value)
}

function clearFilters() {
  filters.value = {
    type: '',
    difficulty: '',
    is_active: true,
  }
  questStore.fetchQuests()
}

function goToPage(page) {
  questStore.fetchQuests({ ...filters.value, page })
}

function getUserAssignment(questId) {
  return userAssignments.value.find(a => a.quest_id === questId)
}

function canAcceptQuest(quest) {
  if (quest.type !== 'bounty') return false
  if (!quest.is_active) return false
  if (getUserAssignment(quest.id)) return false

  const slotWeight = getSlotWeight(quest.priority)
  return wipSlots.value.available >= slotWeight
}

async function handleAccept(quest) {
  acceptingQuestId.value = quest.id
  try {
    await questStore.acceptQuest(quest.id)
    await questStore.fetchAssignments()
    await questStore.fetchWipSlots()
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to accept quest')
  } finally {
    acceptingQuestId.value = null
  }
}

function handleViewDetails(quest) {
  selectedQuest.value = quest
  selectedQuestUserAssignment.value = getUserAssignment(quest.id)
  showDetailsModal.value = true
}

async function handleCreateQuest() {
  creating.value = true
  try {
    await questStore.createQuest(newQuest.value)
    showCreateModal.value = false
    newQuest.value = {
      title: '',
      description: '',
      type: 'bounty',
      priority: 'mid',
      start_date: '',
      due_date: '',
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to create quest')
  } finally {
    creating.value = false
  }
}

async function handleDelete(questId) {
  if (!confirm('Are you sure you want to delete this quest?')) return

  try {
    await questStore.deleteQuest(questId)
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete quest')
  }
}

function getSlotWeight(priority) {
  const weights = { high: 4, mid: 2, low: 1 }
  return weights[priority] || 2
}

function formatType(type) {
  const types = { assigned: 'Assigned', bounty: 'Bounty', usulan: 'Usulan' }
  return types[type] || type
}

function formatDifficulty(difficulty) {
  const difficulties = { high: 'High', mid: 'Mid', low: 'Low' }
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
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
</script>

<style scoped>
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

.type-assigned { background: #dbeafe; color: #1d4ed8; }
.type-bounty { background: #fef3c7; color: #b45309; }
.type-usulan { background: #e0e7ff; color: #4338ca; }

.difficulty-high { background: #fee2e2; color: #dc2626; }
.difficulty-mid { background: #fef3c7; color: #d97706; }
.difficulty-low { background: #dcfce7; color: #16a34a; }

.status-open { color: #b45309; }
.status-assigned { color: #1d4ed8; }
.status-active { color: #16a34a; }
.status-paused { color: #b45309; }
.status-in_review { color: #4338ca; }
.status-approved { color: #16a34a; }
.status-revise { color: #dc2626; }
.status-cancelled,
.status-failed { color: #6b7280; }
</style>
