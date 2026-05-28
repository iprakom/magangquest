<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Quest Logbook</h1>
            <p class="text-slate-400 text-sm">Track your quest history and progress</p>
          </div>
          <div class="flex items-center gap-4">
            <!-- WIP Slots Display -->
            <div class="flex items-center gap-2 bg-slate-700/50 px-3 py-1.5 rounded-lg">
              <span class="text-slate-400 text-sm">Slot:</span>
              <span class="text-white font-semibold">{{ wipSlots.used }}/{{ wipSlots.max }}</span>
              <div class="w-20 h-1.5 bg-slate-600 rounded-full overflow-hidden ml-1">
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="slotUtilizationClass"
                  :style="{ width: `${Math.min((wipSlots.used / wipSlots.max) * 100, 100)}%` }"
                ></div>
              </div>
            </div>
            <button
              @click="$inertia.visit('/quests')"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
              <span>🎯</span>
              Browse Quests
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Stats Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
          <p class="text-3xl font-bold text-green-400">{{ stats.approved }}</p>
          <p class="text-slate-400 text-sm">Completed</p>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
          <p class="text-3xl font-bold text-yellow-400">{{ stats.in_review }}</p>
          <p class="text-slate-400 text-sm">In Review</p>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
          <p class="text-3xl font-bold text-blue-400">{{ stats.active }}</p>
          <p class="text-slate-400 text-sm">Active</p>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
          <p class="text-3xl font-bold text-red-400">{{ stats.failed }}</p>
          <p class="text-slate-400 text-sm">Failed</p>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 mb-6">
        <div class="flex flex-wrap border-b border-slate-700">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="px-6 py-3 text-sm font-medium transition-colors relative"
            :class="activeTab === tab.value ? 'text-blue-400' : 'text-slate-400 hover:text-slate-300'"
          >
            {{ tab.label }}
            <span
              v-if="tab.count > 0"
              class="ml-2 px-2 py-0.5 text-xs rounded-full"
              :class="activeTab === tab.value ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300'"
            >
              {{ tab.count }}
            </span>
            <div
              v-if="activeTab === tab.value"
              class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500"
            ></div>
          </button>
        </div>

        <!-- Priority Filter -->
        <div class="px-6 py-3 flex items-center gap-4 border-b border-slate-700">
          <span class="text-slate-400 text-sm">Priority:</span>
          <div class="flex gap-2">
            <button
              v-for="p in ['all', 'high', 'mid', 'low']"
              :key="p"
              @click="priorityFilter = p"
              class="px-3 py-1 text-xs rounded-full transition-colors"
              :class="priorityFilter === p
                ? 'bg-slate-600 text-white'
                : 'bg-slate-700 text-slate-400 hover:bg-slate-600'"
            >
              {{ p === 'all' ? 'All' : p.charAt(0).toUpperCase() + p.slice(1) }}
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-900/50 border border-red-700 rounded-lg p-6 text-center">
        <p class="text-red-300">{{ error }}</p>
        <button @click="loadAssignments" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredAssignments.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-12 text-center">
        <span class="text-6xl mb-4 block">📖</span>
        <h3 class="text-lg font-medium text-white mb-2">No quests found</h3>
        <p class="text-slate-400 mb-4">
          {{ activeTab === 'all' ? 'Start your adventure by accepting a quest!' : `No ${activeTab.replace('_', ' ')} quests.` }}
        </p>
        <button
          v-if="activeTab === 'all'"
          @click="$inertia.visit('/quests')"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Browse Quests
        </button>
      </div>

      <!-- Quest List -->
      <div v-else class="space-y-4">
        <div
          v-for="assignment in paginatedAssignments"
          :key="assignment.id"
          class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 hover:border-slate-600 transition-colors"
        >
          <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <!-- Left: Quest Info -->
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <PriorityBadge :priority="assignment.quest?.priority || 'mid'" />
                <StatusBadge :status="assignment.status" />
                <span
                  class="px-2 py-0.5 text-xs rounded-full"
                  :class="getTypeClass(assignment.quest?.type)"
                >
                  {{ formatType(assignment.quest?.type) }}
                </span>
              </div>

              <h3 class="text-white font-semibold text-lg mb-1">
                {{ assignment.quest?.title || 'Unknown Quest' }}
              </h3>

              <p v-if="assignment.quest?.description" class="text-slate-400 text-sm mb-3">
                {{ assignment.quest.description.substring(0, 120) }}{{ assignment.quest.description.length > 120 ? '...' : '' }}
              </p>

              <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
                <span class="flex items-center gap-1">
                  <span>⚡</span>
                  {{ getSlotWeight(assignment.quest?.priority) }} XP
                </span>
                <span v-if="assignment.quest?.due_date" class="flex items-center gap-1">
                  <span>📅</span>
                  Due {{ formatDate(assignment.quest?.due_date) }}
                </span>
                <span v-if="assignment.quest?.creator" class="flex items-center gap-1">
                  <span>👤</span>
                  {{ assignment.quest.creator.name }}
                </span>
              </div>
            </div>

            <!-- Right: Actions & Meta -->
            <div class="flex flex-col items-end gap-2">
              <div class="text-right">
                <p class="text-slate-400 text-xs">Started</p>
                <p class="text-white text-sm">{{ formatDate(assignment.created_at) }}</p>
              </div>

              <div v-if="assignment.mentor_notes" class="mt-2 p-2 bg-slate-700/50 rounded-lg max-w-xs">
                <p class="text-slate-400 text-xs">Mentor Notes:</p>
                <p class="text-slate-300 text-sm">{{ assignment.mentor_notes }}</p>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-2 mt-2">
                <!-- Add Progress Button (for active assignments) -->
                <button
                  v-if="canAddProgress(assignment)"
                  @click="openProgressModal(assignment)"
                  class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
                >
                  + Progress
                </button>

                <!-- Submit for Review Button (for active assignments) -->
                <button
                  v-if="canSubmitForReview(assignment)"
                  @click="handleSubmitForReview(assignment)"
                  :disabled="submittingReview"
                  class="px-3 py-1.5 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-sm disabled:opacity-50"
                >
                  Submit Review
                </button>

                <button
                  @click="$inertia.visit(`/quests/${assignment.quest_id}`)"
                  class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-sm"
                >
                  View Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center gap-2">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="px-4 py-2 rounded-lg"
          :class="currentPage === 1 ? 'bg-slate-800 text-slate-500 cursor-not-allowed' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
        >
          ← Previous
        </button>

        <button
          v-for="page in visiblePages"
          :key="page"
          @click="currentPage = page"
          class="px-4 py-2 rounded-lg"
          :class="page === currentPage ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
        >
          {{ page }}
        </button>

        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="px-4 py-2 rounded-lg"
          :class="currentPage === totalPages ? 'bg-slate-800 text-slate-500 cursor-not-allowed' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
        >
          Next →
        </button>
      </div>
    </main>

    <!-- Progress Entry Modal -->
    <div v-if="showProgressModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-lg">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">Add Progress Entry</h2>
            <button @click="closeProgressModal" class="text-slate-400 hover:text-white text-2xl">&times;</button>
          </div>

          <div v-if="selectedAssignment" class="mb-4">
            <p class="text-slate-400 text-sm">Quest:</p>
            <p class="text-white font-medium">{{ selectedAssignment.quest?.title }}</p>
          </div>

          <form @submit.prevent="handleAddProgress">
            <div class="mb-4">
              <label class="block text-slate-300 text-sm font-medium mb-2">Progress Notes</label>
              <textarea
                v-model="progressNotes"
                rows="4"
                required
                maxlength="2000"
                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-400 focus:outline-none focus:border-blue-500"
                placeholder="Describe your progress..."
              ></textarea>
              <p class="text-slate-500 text-xs mt-1">{{ progressNotes.length }}/2000 characters</p>
            </div>

            <div class="mb-4">
              <label class="block text-slate-300 text-sm font-medium mb-2">Evidence (optional)</label>
              <div class="border-2 border-dashed border-slate-600 rounded-lg p-4 text-center hover:border-slate-500 transition-colors">
                <input
                  type="file"
                  id="evidence"
                  @change="handleEvidenceChange"
                  accept="image/jpeg,image/png,image/jpg,application/pdf"
                  class="hidden"
                />
                <label for="evidence" class="cursor-pointer">
                  <span class="text-4xl mb-2 block">📎</span>
                  <span class="text-slate-400 text-sm">
                    {{ evidenceFile ? evidenceFile.name : 'Click to upload or drag file here' }}
                  </span>
                  <p class="text-slate-500 text-xs mt-1">JPG, PNG, PDF (max 10MB)</p>
                </label>
              </div>
              <button
                v-if="evidenceFile"
                type="button"
                @click="evidenceFile = null"
                class="mt-2 text-red-400 text-sm hover:text-red-300"
              >
                Remove file
              </button>
            </div>

            <div class="flex justify-end gap-3">
              <button
                type="button"
                @click="closeProgressModal"
                class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="addingProgress || !progressNotes.trim()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
              >
                {{ addingProgress ? 'Adding...' : '+ Add Progress (+10 Points)' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuestStore } from '@/stores/quest'
import PriorityBadge from '@/components/PriorityBadge.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const questStore = useQuestStore()

const loading = ref(true)
const error = ref(null)
const activeTab = ref('all')
const priorityFilter = ref('all')
const currentPage = ref(1)
const perPage = 10

// Progress modal state
const showProgressModal = ref(false)
const selectedAssignment = ref(null)
const progressNotes = ref('')
const evidenceFile = ref(null)
const addingProgress = ref(false)
const submittingReview = ref(false)

const assignments = computed(() => questStore.assignments)
const wipSlots = computed(() => questStore.wipSlots)

const stats = computed(() => {
  const all = assignments.value
  return {
    approved: all.filter(a => a.status === 'approved').length,
    in_review: all.filter(a => a.status === 'in_review').length,
    active: all.filter(a => ['assigned', 'active'].includes(a.status)).length,
    failed: all.filter(a => ['failed', 'cancelled'].includes(a.status)).length,
  }
})

const tabs = computed(() => [
  { label: 'All', value: 'all', count: assignments.value.length },
  { label: 'Active', value: 'active', count: stats.value.active },
  { label: 'In Review', value: 'in_review', count: stats.value.in_review },
  { label: 'Completed', value: 'approved', count: stats.value.approved },
  { label: 'Failed', value: 'failed', count: stats.value.failed },
])

const filteredAssignments = computed(() => {
  let filtered = assignments.value

  // Status filter
  if (activeTab.value !== 'all') {
    if (activeTab.value === 'active') {
      filtered = filtered.filter(a => ['assigned', 'active'].includes(a.status))
    } else {
      filtered = filtered.filter(a => a.status === activeTab.value)
    }
  }

  // Priority filter
  if (priorityFilter.value !== 'all') {
    filtered = filtered.filter(a => a.quest?.priority === priorityFilter.value)
  }

  // Sort by created_at desc
  return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const totalPages = computed(() => Math.ceil(filteredAssignments.value.length / perPage))

const paginatedAssignments = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredAssignments.value.slice(start, start + perPage)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value

  let start = Math.max(1, current - 2)
  let end = Math.min(total, start + 4)

  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const slotUtilizationClass = computed(() => {
  const percent = (wipSlots.value.used / wipSlots.value.max) * 100
  if (percent >= 100) return 'bg-red-500'
  if (percent >= 75) return 'bg-yellow-500'
  if (percent >= 50) return 'bg-blue-500'
  return 'bg-green-500'
})

const slotWeights = { high: 4, mid: 2, low: 1 }

function getSlotWeight(priority) {
  return slotWeights[priority] || 2
}

function formatType(type) {
  const types = { assigned: 'Assigned', bounty: 'Bounty', usulan: 'Usulan' }
  return types[type] || type
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function getTypeClass(type) {
  const classes = {
    assigned: 'bg-blue-900/50 text-blue-300',
    bounty: 'bg-amber-900/50 text-amber-300',
    usulan: 'bg-purple-900/50 text-purple-300',
  }
  return classes[type] || 'bg-slate-700 text-slate-300'
}

function canAddProgress(assignment) {
  return ['assigned', 'active'].includes(assignment.status)
}

function canSubmitForReview(assignment) {
  return assignment.status === 'active'
}

// Reset page when filters change
watch([activeTab, priorityFilter], () => {
  currentPage.value = 1
})

async function loadAssignments() {
  loading.value = true
  error.value = null

  try {
    await questStore.fetchAssignments()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load quests'
    console.error('Quest logbook load error:', err)
  } finally {
    loading.value = false
  }
}

function openProgressModal(assignment) {
  selectedAssignment.value = assignment
  progressNotes.value = ''
  evidenceFile.value = null
  showProgressModal.value = true
}

function closeProgressModal() {
  showProgressModal.value = false
  selectedAssignment.value = null
  progressNotes.value = ''
  evidenceFile.value = null
}

function handleEvidenceChange(event) {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('File size exceeds 10MB limit')
      return
    }
    evidenceFile.value = file
  }
}

async function handleAddProgress() {
  if (!selectedAssignment.value || !progressNotes.value.trim()) return

  addingProgress.value = true
  try {
    await questStore.addProgress(
      selectedAssignment.value.id,
      progressNotes.value,
      evidenceFile.value
    )
    closeProgressModal()
    await loadAssignments() // Refresh to show updated status
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to add progress')
  } finally {
    addingProgress.value = false
  }
}

async function handleSubmitForReview(assignment) {
  if (!confirm('Submit this quest for review? You won\'t be able to add more progress after this.')) return

  submittingReview.value = true
  try {
    await questStore.submitForReview(assignment.id)
    await loadAssignments() // Refresh to show updated status
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit for review')
  } finally {
    submittingReview.value = false
  }
}

onMounted(() => {
  loadAssignments()
})
</script>
