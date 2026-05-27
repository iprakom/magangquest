<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  assignmentId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'submitted'])

const isLoading = ref(false)
const progressEntries = ref([])
const totalPoints = ref(0)
const notes = ref('')
const evidenceFile = ref(null)
const error = ref('')
const success = ref('')

const canSubmit = computed(() => {
  return notes.value.trim().length > 0
})

async function fetchProgress() {
  try {
    const response = await fetch(`/api/quest-assignments/${props.assignmentId}/progress`)
    if (response.ok) {
      const data = await response.json()
      progressEntries.value = data.progress_entries
      totalPoints.value = data.total_points_earned
    }
  } catch (e) {
    console.error('Failed to fetch progress:', e)
  }
}

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      error.value = 'File size must be less than 10MB'
      evidenceFile.value = null
    } else {
      error.value = ''
      evidenceFile.value = file
    }
  }
}

async function submitProgress() {
  if (!canSubmit.value) return

  isLoading.value = true
  error.value = ''
  success.value = ''

  const formData = new FormData()
  formData.append('notes', notes.value)
  if (evidenceFile.value) {
    formData.append('evidence', evidenceFile.value)
  }

  try {
    const response = await fetch(`/api/quest-assignments/${props.assignmentId}/progress`, {
      method: 'POST',
      body: formData
    })

    if (response.ok) {
      const data = await response.json()
      success.value = `Progress added! +${data.points_earned} points earned.`
      notes.value = ''
      evidenceFile.value = null
      await fetchProgress()
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to add progress'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

async function submitForReview() {
  isLoading.value = true
  error.value = ''

  try {
    const response = await fetch(`/api/quest-assignments/${props.assignmentId}/submit-review`, {
      method: 'POST'
    })

    if (response.ok) {
      emit('submitted')
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to submit for review'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchProgress()
})
</script>

<template>
  <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-xl font-bold">Quest Progress Log</h3>
      <button @click="$emit('close')" class="text-gray-400 hover:text-white">
        ✕
      </button>
    </div>

    <!-- Progress Entries -->
    <div class="mb-6">
      <h4 class="text-sm font-semibold text-gray-400 mb-3">Progress History</h4>
      <div v-if="progressEntries.length === 0" class="text-gray-500 text-sm">
        No progress entries yet. Add your first progress update below.
      </div>
      <div v-else class="space-y-3 max-h-60 overflow-y-auto">
        <div v-for="entry in progressEntries" :key="entry.id" 
          class="bg-dark-700 rounded-lg p-3 border border-dark-600">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs text-gray-400">{{ formatDate(entry.created_at) }}</span>
            <span class="text-xs text-accent-gold">+{{ entry.points_earned }} XP</span>
          </div>
          <p class="text-sm">{{ entry.notes }}</p>
          <a v-if="entry.evidence_path" :href="`/storage/${entry.evidence_path}`" 
            target="_blank"
            class="text-xs text-blue-400 hover:text-blue-300 mt-1 inline-block">
            📎 {{ entry.evidence_filename || 'View Evidence' }}
          </a>
        </div>
      </div>
      <div v-if="totalPoints > 0" class="mt-3 text-sm text-gray-400">
        Total points from progress: <span class="text-accent-gold font-semibold">+{{ totalPoints }}</span>
      </div>
    </div>

    <!-- Add Progress Form -->
    <div class="border-t border-dark-700 pt-4">
      <h4 class="text-sm font-semibold text-gray-400 mb-3">Add Progress Entry</h4>
      
      <div v-if="error" class="bg-red-900/30 border border-red-700 rounded-lg p-3 mb-4 text-red-400 text-sm">
        {{ error }}
      </div>
      
      <div v-if="success" class="bg-green-900/30 border border-green-700 rounded-lg p-3 mb-4 text-green-400 text-sm">
        {{ success }}
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm text-gray-400 mb-2">Progress Notes</label>
          <textarea v-model="notes" rows="3" 
            class="w-full bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white text-sm
              focus:border-accent-gold focus:outline-none"
            placeholder="Describe your progress..."></textarea>
        </div>

        <div>
          <label class="block text-sm text-gray-400 mb-2">Evidence (optional)</label>
          <input type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.pdf"
            class="block w-full text-sm text-gray-400
              file:mr-4 file:py-2 file:px-4
              file:rounded-lg file:border-0
              file:text-sm file:font-semibold
              file:bg-dark-600 file:text-white
              hover:file:bg-dark-500" />
          <p class="text-xs text-gray-500 mt-1">Max 10MB. JPG, PNG, or PDF.</p>
        </div>

        <div class="flex gap-3">
          <button @click="submitProgress" :disabled="!canSubmit || isLoading"
            class="flex-1 bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600 disabled:text-gray-500
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            {{ isLoading ? 'Saving...' : '+ Add Progress (+10 XP)' }}
          </button>
          <button @click="submitForReview" :disabled="isLoading"
            class="bg-green-700 hover:bg-green-600 disabled:bg-dark-600 disabled:text-gray-500
              text-white font-semibold py-2 px-4 rounded-lg transition-colors">
            Submit for Review
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
