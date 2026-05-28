<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <button
              @click="$inertia.visit('/quests')"
              class="text-slate-400 hover:text-white transition-colors"
            >
              <span class="text-2xl">←</span>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-white">Quest Details</h1>
              <p class="text-slate-400 text-sm">View quest information and progress</p>
            </div>
          </div>
          <div v-if="userAssignment" class="flex items-center gap-3">
            <StatusBadge :status="userAssignment.status" />
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-900/50 border border-red-700 rounded-lg p-6 text-center">
        <p class="text-red-300">{{ error }}</p>
        <button 
          @click="loadQuest" 
          class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Try Again
        </button>
      </div>

      <!-- Quest Content -->
      <div v-else-if="quest" class="space-y-6">
        <!-- Quest Info Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <!-- Header with badges -->
          <div class="flex flex-wrap items-center gap-3 mb-4">
            <span 
              class="px-3 py-1 text-xs rounded-full font-semibold uppercase"
              :class="getTypeClass(quest.type)"
            >
              {{ formatType(quest.type) }}
            </span>
            <span 
              class="px-3 py-1 text-xs rounded-full font-semibold uppercase"
              :class="getDifficultyClass(quest.priority)"
            >
              {{ formatDifficulty(quest.priority) }}
            </span>
            <span 
              v-if="!quest.is_active"
              class="px-3 py-1 text-xs rounded-full font-semibold uppercase bg-red-900/50 text-red-300"
            >
              Inactive
            </span>
          </div>

          <!-- Title & Description -->
          <h2 class="text-2xl font-bold text-white mb-3">{{ quest.title }}</h2>
          <p v-if="quest.description" class="text-slate-300 mb-4 whitespace-pre-wrap">
            {{ quest.description }}
          </p>

          <!-- Meta Info Grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-700">
            <div>
              <p class="text-slate-500 text-xs uppercase tracking-wide mb-1">XP Reward</p>
              <p class="text-white font-semibold flex items-center gap-1">
                <span>⚡</span> {{ getSlotWeight(quest.priority) }} XP
              </p>
            </div>
            <div v-if="quest.due_date">
              <p class="text-slate-500 text-xs uppercase tracking-wide mb-1">Due Date</p>
              <p class="text-white font-semibold flex items-center gap-1">
                <span>📅</span> {{ formatDate(quest.due_date) }}
              </p>
            </div>
            <div v-if="quest.start_date">
              <p class="text-slate-500 text-xs uppercase tracking-wide mb-1">Start Date</p>
              <p class="text-white font-semibold flex items-center gap-1">
                <span>🗓️</span> {{ formatDate(quest.start_date) }}
              </p>
            </div>
            <div v-if="quest.creator">
              <p class="text-slate-500 text-xs uppercase tracking-wide mb-1">Created By</p>
              <p class="text-white font-semibold flex items-center gap-1">
                <span>👤</span> {{ quest.creator.name }}
              </p>
            </div>
          </div>

          <!-- Assigned Intern (for assigned quests) -->
          <div v-if="userAssignment && userAssignment.user" class="mt-6 pt-6 border-t border-slate-700">
            <p class="text-slate-500 text-xs uppercase tracking-wide mb-2">Assigned To</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                {{ userAssignment.user.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="text-white font-medium">{{ userAssignment.user.name }}</p>
                <p class="text-slate-400 text-sm">{{ userAssignment.user.email }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="mt-6 pt-6 border-t border-slate-700 flex flex-wrap gap-3">
            <button
              v-if="canAddProgress"
              @click="openProgressModal"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
            >
              <span>+</span> Add Progress
            </button>
            <button
              v-if="canSubmitForReview"
              @click="handleSubmitForReview"
              :disabled="submittingReview"
              class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <span>📤</span> Submit for Review
            </button>
          </div>
        </div>

        <!-- Progress Entries Section -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>📋</span> Progress Entries
          </h3>

          <!-- Loading Progress -->
          <div v-if="loadingProgress" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>

          <!-- Empty Progress -->
          <div v-else-if="progressEntries.length === 0" class="text-center py-8">
            <span class="text-5xl mb-3 block">📝</span>
            <p class="text-slate-400">No progress entries yet</p>
            <p class="text-slate-500 text-sm mt-1">Start working on this quest and add progress!</p>
          </div>

          <!-- Progress List -->
          <div v-else class="space-y-4">
            <div 
              v-for="entry in progressEntries" 
              :key="entry.id"
              class="bg-slate-700/50 rounded-lg p-4 border border-slate-600"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <p class="text-slate-300 whitespace-pre-wrap">{{ entry.notes }}</p>
                  
                  <!-- Evidence Files -->
                  <div v-if="entry.evidence_path" class="mt-3">
                    <a 
                      :href="`/storage/${entry.evidence_path}`"
                      target="_blank"
                      class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-600 hover:bg-slate-500 rounded-lg text-sm text-slate-200 transition-colors"
                    >
                      <span>📎</span>
                      <span>{{ entry.evidence_filename || 'View Evidence' }}</span>
                    </a>
                  </div>
                </div>
                
                <div class="text-right shrink-0">
                  <div class="flex items-center gap-2 text-sm">
                    <span class="text-green-400 font-medium">+{{ entry.points_earned }} XP</span>
                  </div>
                  <p class="text-slate-500 text-xs mt-1">{{ formatDateTime(entry.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Other Assignments (for admin/mentor view) -->
        <div v-if="quest.assignments && quest.assignments.length > 0 && isAdmin" class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>👥</span> All Assignments
          </h3>
          
          <div class="space-y-3">
            <div 
              v-for="assignment in quest.assignments" 
              :key="assignment.id"
              class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg border border-slate-600"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-semibold">
                  {{ assignment.user?.name?.charAt(0).toUpperCase() || '?' }}
                </div>
                <div>
                  <p class="text-white font-medium">{{ assignment.user?.name || 'Unknown' }}</p>
                  <p class="text-slate-400 text-xs">{{ assignment.user?.email }}</p>
                </div>
              </div>
              <StatusBadge :status="assignment.status" />
            </div>
          </div>
        </div>
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

          <div v-if="quest" class="mb-4">
            <p class="text-slate-400 text-sm">Quest:</p>
            <p class="text-white font-medium">{{ quest.title }}</p>
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
                <label for="evidence" class="cursor-pointer block">
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
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StatusBadge from '@/components/StatusBadge.vue'

const page = usePage()
const questStore = useQuestStore()

const quest = ref(null)
const userAssignment = ref(null)
const progressEntries = ref([])
const loading = ref(true)
const loadingProgress = ref(false)
const error = ref(null)
const submittingReview = ref(false)

// Progress modal state
const showProgressModal = ref(false)
const progressNotes = ref('')
const evidenceFile = ref(null)
const addingProgress = ref(false)

const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')
const isMentor = computed(() => page.props.auth?.user?.role === 'mentor')

const canAddProgress = computed(() => {
  if (!userAssignment.value) return false
  return ['assigned', 'active'].includes(userAssignment.value.status)
})

const canSubmitForReview = computed(() => {
  if (!userAssignment.value) return false
  return userAssignment.value.status === 'active'
})

const slotWeights = { high: 4, mid: 2, low: 1 }

function getSlotWeight(priority) {
  return slotWeights[priority] || 2
}

function formatType(type) {
  const types = { assigned: 'Assigned', bounty: 'Bounty', usulan: 'Usulan' }
  return types[type] || type
}

function formatDifficulty(difficulty) {
  const difficulties = { high: 'High', mid: 'Mid', low: 'Low' }
  return difficulties[difficulty] || difficulty
}

function getTypeClass(type) {
  const classes = {
    assigned: 'bg-blue-900/50 text-blue-300',
    bounty: 'bg-amber-900/50 text-amber-300',
    usulan: 'bg-purple-900/50 text-purple-300',
  }
  return classes[type] || 'bg-slate-700 text-slate-300'
}

function getDifficultyClass(difficulty) {
  const classes = {
    high: 'bg-red-900/50 text-red-300',
    mid: 'bg-yellow-900/50 text-yellow-300',
    low: 'bg-green-900/50 text-green-300',
  }
  return classes[difficulty] || 'bg-slate-700 text-slate-300'
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function formatDateTime(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

async function loadQuest() {
  loading.value = true
  error.value = null
  
  const questId = page.url.split('/').pop()
  
  try {
    const response = await axios.get(`/api/quests/${questId}`)
    quest.value = response.data.quest
    userAssignment.value = response.data.user_assignment
    
    // Load progress if user has assignment
    if (userAssignment.value) {
      await loadProgress()
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load quest'
    console.error('Quest load error:', err)
  } finally {
    loading.value = false
  }
}

async function loadProgress() {
  if (!userAssignment.value) return
  
  loadingProgress.value = true
  try {
    const response = await axios.get(`/api/quest-assignments/${userAssignment.value.id}/progress`)
    progressEntries.value = response.data.progress_entries
  } catch (err) {
    console.error('Failed to load progress:', err)
  } finally {
    loadingProgress.value = false
  }
}

function openProgressModal() {
  progressNotes.value = ''
  evidenceFile.value = null
  showProgressModal.value = true
}

function closeProgressModal() {
  showProgressModal.value = false
  progressNotes.value = ''
  evidenceFile.value = null
}

function handleEvidenceChange(event) {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('File size must be less than 10MB')
      return
    }
    evidenceFile.value = file
  }
}

async function handleAddProgress() {
  if (!userAssignment.value || !progressNotes.value.trim()) return
  
  addingProgress.value = true
  try {
    await questStore.addProgress(
      userAssignment.value.id,
      progressNotes.value,
      evidenceFile.value
    )
    
    // Reload progress entries
    await loadProgress()
    
    // Update assignment status if needed
    if (userAssignment.value.status === 'assigned') {
      userAssignment.value.status = 'active'
    }
    
    closeProgressModal()
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to add progress')
  } finally {
    addingProgress.value = false
  }
}

async function handleSubmitForReview() {
  if (!userAssignment.value) return
  
  if (!confirm('Submit this quest for review? You won\'t be able to add more progress after that.')) {
    return
  }
  
  submittingReview.value = true
  try {
    await questStore.submitForReview(userAssignment.value.id)
    
    // Update local state
    userAssignment.value.status = 'in_review'
    
    alert('Quest submitted for review!')
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit for review')
  } finally {
    submittingReview.value = false
  }
}

// Load quest on mount
onMounted(() => {
  loadQuest()
})
</script>
