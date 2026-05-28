<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Review Quest</h1>
            <p class="text-slate-400 text-sm">Validasi dan review quest yang submitted oleh intern</p>
          </div>
          <Link
            href="/mentor/dashboard"
            class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors flex items-center gap-2"
          >
            ← Kembali
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">📋</span>
            <span class="text-slate-400 text-sm">Total</span>
          </div>
          <p class="text-4xl font-bold text-white">{{ pendingAssignments.length }}</p>
          <p class="text-slate-400 text-sm">Quest Pending Review</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-yellow-700/50 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">⚠️</span>
            <span class="text-yellow-400 text-sm">Warning</span>
          </div>
          <p class="text-4xl font-bold text-yellow-400">{{ warningCount }}</p>
          <p class="text-slate-400 text-sm">SLA &lt; 24 jam</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-red-700/50 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">🚨</span>
            <span class="text-red-400 text-sm">Critical</span>
          </div>
          <p class="text-4xl font-bold text-red-400">{{ criticalCount }}</p>
          <p class="text-slate-400 text-sm">SLA Melewati Batas</p>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-900/50 border border-red-700 rounded-lg p-6 mb-6">
        <p class="text-red-300">{{ error }}</p>
        <button @click="fetchPendingAssignments" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="pendingAssignments.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-12 text-center">
        <span class="text-6xl mb-4 block">✅</span>
        <h3 class="text-lg font-medium text-white mb-2">Tidak ada quest pending review</h3>
        <p class="text-slate-400">Semua quest sudah divalidasi. Great job!</p>
      </div>

      <!-- Assignments List -->
      <div v-else class="space-y-6">
        <div
          v-for="assignment in pendingAssignments"
          :key="assignment.id"
          class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden"
        >
          <!-- Assignment Header -->
          <div class="p-6 border-b border-slate-700">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-xl font-semibold text-white">{{ assignment.quest.title }}</h3>
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                    :class="getSlaBadgeClass(assignment)"
                  >
                    {{ getSlaLabel(assignment) }}
                  </span>
                </div>
                <p class="text-slate-400 text-sm mb-3">
                  Submitted oleh <span class="text-white font-medium">{{ assignment.user.name }}</span>
                  <span class="mx-2">•</span>
                  {{ formatDate(assignment.submitted_at) }}
                </p>
                <p v-if="assignment.quest.description" class="text-slate-300 text-sm">
                  {{ assignment.quest.description }}
                </p>
              </div>
              <div class="text-right ml-4">
                <div class="text-2xl font-bold" :class="getSlaTextClass(assignment)">
                  {{ getSlaCountdown(assignment) }}
                </div>
                <div class="text-slate-400 text-xs">SLA Countdown</div>
              </div>
            </div>
          </div>

          <!-- SLA Info -->
          <div class="px-6 py-3 bg-slate-700/30 border-b border-slate-700">
            <div class="flex items-center gap-4 text-sm">
              <span class="text-slate-400">
                <strong>Submitted:</strong> {{ formatDateTime(assignment.submitted_at) }}
              </span>
              <span class="text-slate-400">
                <strong>SLA Deadline:</strong> {{ formatDateTime(assignment.sla_deadline) }}
              </span>
              <span class="text-slate-400">
                <strong>Priority:</strong> {{ assignment.quest.priority.toUpperCase() }}
              </span>
              <span class="text-slate-400">
                <strong>Evidence:</strong> {{ assignment.progress_count || 0 }} files
              </span>
            </div>
          </div>

          <!-- Evidence Section -->
          <div v-if="assignment.progress && assignment.progress.length > 0" class="px-6 py-4 border-b border-slate-700">
            <h4 class="text-sm font-medium text-slate-300 mb-3">Evidence / Progress:</h4>
            <div class="space-y-2">
              <div
                v-for="progress in assignment.progress"
                :key="progress.id"
                class="flex items-center justify-between bg-slate-700/30 rounded-lg p-3"
              >
                <div class="flex-1">
                  <p class="text-white text-sm">{{ progress.notes }}</p>
                  <p class="text-slate-400 text-xs mt-1">{{ formatDateTime(progress.created_at) }}</p>
                </div>
                <div v-if="progress.evidence_path" class="ml-4">
                  <a
                    :href="`/storage/${progress.evidence_path}`"
                    target="_blank"
                    class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm"
                  >
                    📎 {{ progress.evidence_filename || 'View Evidence' }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Mentor Notes -->
          <div v-if="assignment.mentor_notes" class="px-6 py-4 border-b border-slate-700 bg-slate-700/20">
            <h4 class="text-sm font-medium text-slate-300 mb-2">Catatan Mentor:</h4>
            <p class="text-slate-300 text-sm whitespace-pre-line">{{ assignment.mentor_notes }}</p>
          </div>

          <!-- Actions -->
          <div class="p-6 flex items-center justify-end gap-4">
            <button
              @click="openReviseModal(assignment)"
              class="px-6 py-3 bg-yellow-600/20 text-yellow-400 border border-yellow-600/30 rounded-lg hover:bg-yellow-600/30 transition-colors flex items-center gap-2"
            >
              🔄 Revise
            </button>
            <button
              @click="approveAssignment(assignment)"
              :disabled="actionLoading === assignment.id"
              class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="actionLoading === assignment.id" class="animate-spin">⏳</span>
              ✅ Approve (+100 Poin)
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Revise Modal -->
    <div v-if="showReviseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
      <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-lg mx-4">
        <div class="p-6 border-b border-slate-700">
          <h3 class="text-xl font-semibold text-white">Revisi Quest</h3>
          <p class="text-slate-400 text-sm mt-1">Berikan catatan untuk perbaikan quest</p>
        </div>
        <div class="p-6">
          <label class="block text-sm font-medium text-slate-300 mb-2">
            Catatan Revisi <span class="text-red-400">*</span>
          </label>
          <textarea
            v-model="reviseNotes"
            rows="4"
            required
            placeholder="Jelaskan apa yang perlu diperbaiki..."
            class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500 placeholder-slate-400"
          ></textarea>
        </div>
        <div class="p-6 border-t border-slate-700 flex justify-end gap-4">
          <button
            @click="closeReviseModal"
            class="px-6 py-3 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors"
          >
            Batal
          </button>
          <button
            @click="submitRevise"
            :disabled="!reviseNotes.trim() || actionLoading"
            class="px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="actionLoading" class="animate-spin">⏳</span>
            🔄 Submit Revisi (-10 Poin)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const loading = ref(false)
const error = ref(null)
const pendingAssignments = ref([])
const actionLoading = ref(null)

const showReviseModal = ref(false)
const reviseNotes = ref('')
const selectedAssignment = ref(null)

const warningCount = computed(() => {
  return pendingAssignments.value.filter(a => {
    const hours = getSlaHoursRemaining(a)
    return hours > 0 && hours < 24
  }).length
})

const criticalCount = computed(() => {
  return pendingAssignments.value.filter(a => getSlaHoursRemaining(a) <= 0).length
})

function getSlaHoursRemaining(assignment) {
  if (!assignment.sla_deadline) return 72
  const deadline = new Date(assignment.sla_deadline)
  const now = new Date()
  return Math.max(0, (deadline - now) / (1000 * 60 * 60))
}

function getSlaCountdown(assignment) {
  const hours = getSlaHoursRemaining(assignment)
  if (hours <= 0) return 'LEWAT'
  if (hours < 24) return `${Math.floor(hours)} jam`
  const days = Math.floor(hours / 24)
  const remainingHours = Math.floor(hours % 24)
  return `${days}h ${remainingHours}m`
}

function getSlaLabel(assignment) {
  const hours = getSlaHoursRemaining(assignment)
  if (hours <= 0) return '⚠️ SLA LEWAT'
  if (hours < 24) return '⚡ SLA < 24 jam'
  return '⏳ DALAM PROGRESS'
}

function getSlaBadgeClass(assignment) {
  const hours = getSlaHoursRemaining(assignment)
  if (hours <= 0) return 'bg-red-500/20 text-red-400 border border-red-500/30'
  if (hours < 24) return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'
  return 'bg-green-500/20 text-green-400 border border-green-500/30'
}

function getSlaTextClass(assignment) {
  const hours = getSlaHoursRemaining(assignment)
  if (hours <= 0) return 'text-red-400'
  if (hours < 24) return 'text-yellow-400'
  return 'text-green-400'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function fetchPendingAssignments() {
  loading.value = true
  error.value = null

  try {
    const response = await axios.get('/api/mentor/pending-validations')
    
    // Fetch progress for each assignment
    const assignments = response.data.pending_validations || []
    
    // Fetch progress evidence for each assignment
    const assignmentsWithProgress = await Promise.all(
      assignments.map(async (assignment) => {
        try {
          const progressResponse = await axios.get(`/api/quest-assignments/${assignment.id}/progress`)
          return {
            ...assignment,
            progress: progressResponse.data.progress_entries || [],
            progress_count: progressResponse.data.progress_entries?.length || 0,
          }
        } catch {
          return {
            ...assignment,
            progress: [],
            progress_count: 0,
          }
        }
      })
    )
    
    pendingAssignments.value = assignmentsWithProgress
  } catch (err) {
    console.error('Failed to fetch pending assignments:', err)
    error.value = err.response?.data?.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function openReviseModal(assignment) {
  selectedAssignment.value = assignment
  reviseNotes.value = ''
  showReviseModal.value = true
}

function closeReviseModal() {
  showReviseModal.value = false
  selectedAssignment.value = null
  reviseNotes.value = ''
}

async function approveAssignment(assignment) {
  if (!confirm(`Approve quest "${assignment.quest.title}" dari ${assignment.user.name}?`)) {
    return
  }

  actionLoading.value = assignment.id

  try {
    await axios.put(`/api/mentor/assignments/${assignment.id}/validate`, {
      action: 'approve',
      mentor_notes: '',
    })

    // Remove from list
    pendingAssignments.value = pendingAssignments.value.filter(a => a.id !== assignment.id)
    
    // Show success (could add toast here)
  } catch (err) {
    console.error('Failed to approve:', err)
    alert(err.response?.data?.message || 'Gagal approve quest')
  } finally {
    actionLoading.value = null
  }
}

async function submitRevise() {
  if (!selectedAssignment.value || !reviseNotes.value.trim()) return

  actionLoading.value = selectedAssignment.value.id

  try {
    await axios.put(`/api/mentor/assignments/${selectedAssignment.value.id}/validate`, {
      action: 'revise',
      mentor_notes: reviseNotes.value,
    })

    // Remove from list
    pendingAssignments.value = pendingAssignments.value.filter(a => a.id !== selectedAssignment.value.id)
    closeReviseModal()
  } catch (err) {
    console.error('Failed to revise:', err)
    alert(err.response?.data?.message || 'Gagal merevisi quest')
  } finally {
    actionLoading.value = null
  }
}

onMounted(() => {
  fetchPendingAssignments()
})
</script>
