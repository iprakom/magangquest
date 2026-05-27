<script setup>
import { ref, onMounted, computed } from 'vue'

const isAdmin = true // Should come from user store

const holidays = ref([])
const isLoading = ref(false)
const error = ref('')
const showForm = ref(false)

// Form state
const editingId = ref(null)
const formData = ref({
  date: '',
  name: '',
  type: 'national',
  is_recurring: false
})

const isEditing = computed(() => editingId.value !== null)

async function fetchHolidays() {
  isLoading.value = true
  try {
    const response = await fetch('/api/holidays')
    if (response.ok) {
      const data = await response.json()
      holidays.value = data.holidays
    }
  } catch (e) {
    error.value = 'Failed to fetch holidays'
  } finally {
    isLoading.value = false
  }
}

function openForm(holiday = null) {
  if (holiday) {
    editingId.value = holiday.id
    formData.value = {
      date: holiday.date,
      name: holiday.name,
      type: holiday.type,
      is_recurring: holiday.is_recurring
    }
  } else {
    editingId.value = null
    formData.value = {
      date: '',
      name: '',
      type: 'national',
      is_recurring: false
    }
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingId.value = null
}

async function submitForm() {
  error.value = ''
  
  if (!formData.value.date || !formData.value.name) {
    error.value = 'Date and name are required'
    return
  }

  isLoading.value = true
  
  try {
    const url = isEditing.value ? `/api/holidays/${editingId.value}` : '/api/holidays'
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData.value)
    })

    if (response.ok) {
      await fetchHolidays()
      closeForm()
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to save holiday'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

async function deleteHoliday(id) {
  if (!confirm('Are you sure you want to delete this holiday?')) return
  
  isLoading.value = true
  try {
    const response = await fetch(`/api/holidays/${id}`, { method: 'DELETE' })
    if (response.ok) {
      await fetchHolidays()
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to delete holiday'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function getTypeBadgeClass(type) {
  switch (type) {
    case 'national': return 'bg-red-900/30 text-red-400 border-red-700'
    case 'local': return 'bg-blue-900/30 text-blue-400 border-blue-700'
    case 'company': return 'bg-purple-900/30 text-purple-400 border-purple-700'
    default: return 'bg-gray-900/30 text-gray-400 border-gray-700'
  }
}

onMounted(() => {
  fetchHolidays()
})
</script>

<template>
  <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-xl font-bold">Holiday Calendar</h3>
      <button @click="openForm()" v-if="isAdmin"
        class="bg-accent-gold hover:bg-accent-gold/80 text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
        + Add Holiday
      </button>
    </div>

    <div v-if="error" class="bg-red-900/30 border border-red-700 rounded-lg p-3 mb-4 text-red-400 text-sm">
      {{ error }}
    </div>

    <!-- Holiday List -->
    <div v-if="isLoading && holidays.length === 0" class="text-center py-8 text-gray-500">
      Loading holidays...
    </div>
    
    <div v-else-if="holidays.length === 0" class="text-center py-8 text-gray-500">
      No holidays configured yet.
    </div>

    <div v-else class="space-y-3">
      <div v-for="holiday in holidays" :key="holiday.id"
        class="bg-dark-700 rounded-lg p-4 border border-dark-600 flex justify-between items-center">
        <div>
          <div class="font-semibold">{{ holiday.name }}</div>
          <div class="text-sm text-gray-400">{{ formatDate(holiday.date) }}</div>
          <div class="flex gap-2 mt-2">
            <span :class="['text-xs px-2 py-1 rounded border', getTypeBadgeClass(holiday.type)]">
              {{ holiday.type }}
            </span>
            <span v-if="holiday.is_recurring" class="text-xs px-2 py-1 rounded bg-gray-900/50 text-gray-400 border border-gray-700">
              🔄 Yearly
            </span>
          </div>
        </div>
        <div v-if="isAdmin" class="flex gap-2">
          <button @click="openForm(holiday)" class="text-gray-400 hover:text-white text-sm">
            ✏️ Edit
          </button>
          <button @click="deleteHoliday(holiday.id)" class="text-red-400 hover:text-red-300 text-sm">
            🗑️ Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Form Modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-dark-800 rounded-xl border border-dark-700 p-6 w-full max-w-md">
        <h4 class="text-lg font-bold mb-4">{{ isEditing ? 'Edit Holiday' : 'Add Holiday' }}</h4>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-400 mb-2">Date</label>
            <input type="date" v-model="formData.date"
              class="w-full bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white
                focus:border-accent-gold focus:outline-none" />
          </div>

          <div>
            <label class="block text-sm text-gray-400 mb-2">Holiday Name</label>
            <input type="text" v-model="formData.name"
              class="w-full bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white
                focus:border-accent-gold focus:outline-none"
              placeholder="e.g., Independence Day" />
          </div>

          <div>
            <label class="block text-sm text-gray-400 mb-2">Type</label>
            <select v-model="formData.type"
              class="w-full bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white
                focus:border-accent-gold focus:outline-none">
              <option value="national">National</option>
              <option value="local">Local</option>
              <option value="company">Company</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" id="is_recurring" v-model="formData.is_recurring"
              class="w-4 h-4 rounded border-dark-600 bg-dark-700 text-accent-gold focus:ring-accent-gold" />
            <label for="is_recurring" class="text-sm text-gray-400">Recurring yearly</label>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="submitForm" :disabled="isLoading"
            class="flex-1 bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            {{ isLoading ? 'Saving...' : 'Save' }}
          </button>
          <button @click="closeForm"
            class="flex-1 bg-dark-600 hover:bg-dark-500 text-white py-2 px-4 rounded-lg transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
