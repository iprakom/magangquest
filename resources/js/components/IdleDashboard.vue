<script setup>
import { ref, onMounted, computed } from 'vue'

const dashboardData = ref([])
const byRoom = ref({})
const summary = ref({})
const isLoading = ref(false)
const error = ref('')
const filters = ref({
  room: '',
  mentor_id: ''
})

async function fetchDashboard() {
  isLoading.value = true
  error.value = ''
  
  try {
    const params = new URLSearchParams()
    if (filters.value.room) params.append('room', filters.value.room)
    if (filters.value.mentor_id) params.append('mentor_id', filters.value.mentor_id)
    
    const response = await fetch(`/api/mentor/idle-dashboard?${params}`)
    if (response.ok) {
      const data = await response.json()
      dashboardData.value = data.dashboard
      byRoom.value = data.by_room
      summary.value = data.summary
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to fetch dashboard'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

function getStatusColorClass(status) {
  switch (status) {
    case 'idle': return 'bg-red-900/30 border-red-700 text-red-400'
    case 'optimal': return 'bg-yellow-900/30 border-yellow-700 text-yellow-400'
    case 'overloaded': return 'bg-gray-900/30 border-gray-700 text-gray-400'
    default: return 'bg-dark-700 border-dark-600 text-gray-400'
  }
}

function getUtilizationBarColor(percent) {
  if (percent >= 100) return 'bg-gray-500'
  if (percent <= 50) return 'bg-red-500'
  return 'bg-yellow-500'
}

const rooms = computed(() => {
  return Object.keys(byRoom.value)
})

onMounted(() => {
  fetchDashboard()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">Mentor Dashboard</h2>
      <button @click="fetchDashboard" :disabled="isLoading"
        class="bg-dark-700 hover:bg-dark-600 text-white py-2 px-4 rounded-lg transition-colors">
        {{ isLoading ? 'Loading...' : '🔄 Refresh' }}
      </button>
    </div>

    <div v-if="error" class="bg-red-900/30 border border-red-700 rounded-lg p-4 text-red-400">
      {{ error }}
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 text-center">
        <div class="text-2xl font-bold text-white">{{ summary.total_interns || 0 }}</div>
        <div class="text-sm text-gray-400">Total Interns</div>
      </div>
      <div class="bg-red-900/20 rounded-xl p-4 border border-red-800 text-center">
        <div class="text-2xl font-bold text-red-400">{{ summary.idle_count || 0 }}</div>
        <div class="text-sm text-red-300">Idle (&lt;50%)</div>
      </div>
      <div class="bg-yellow-900/20 rounded-xl p-4 border border-yellow-800 text-center">
        <div class="text-2xl font-bold text-yellow-400">{{ summary.optimal_count || 0 }}</div>
        <div class="text-sm text-yellow-300">Optimal (51-99%)</div>
      </div>
      <div class="bg-gray-900/20 rounded-xl p-4 border border-gray-700 text-center">
        <div class="text-2xl font-bold text-gray-400">{{ summary.overloaded_count || 0 }}</div>
        <div class="text-sm text-gray-300">Overloaded (100%)</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 flex gap-4">
      <div>
        <label class="block text-sm text-gray-400 mb-1">Room</label>
        <input type="text" v-model="filters.room" placeholder="Filter by room..."
          class="bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white text-sm
            focus:border-accent-gold focus:outline-none" />
      </div>
      <div class="flex items-end">
        <button @click="fetchDashboard"
          class="bg-accent-gold hover:bg-accent-gold/80 text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
          Apply Filters
        </button>
      </div>
    </div>

    <!-- Room-grouped view -->
    <div v-for="room in rooms" :key="room" class="space-y-3">
      <h3 class="text-lg font-semibold text-gray-300 flex items-center gap-2">
        <span>🏢</span> {{ room === 'Unassigned' ? 'Unassigned Interns' : room }}
        <span class="text-sm text-gray-500">({{ byRoom[room].length }} interns)</span>
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="intern in byRoom[room]" :key="intern.user_id"
          :class="['rounded-xl p-4 border-2', getStatusColorClass(intern.status)]">
          <div class="flex justify-between items-start mb-3">
            <div>
              <div class="font-semibold text-white">{{ intern.name }}</div>
              <div class="text-xs text-gray-400">{{ intern.email }}</div>
            </div>
            <span :class="['px-2 py-1 rounded text-xs font-semibold', 
              intern.status_color === 'red' ? 'bg-red-900/50 text-red-300' :
              intern.status_color === 'yellow' ? 'bg-yellow-900/50 text-yellow-300' :
              'bg-gray-900/50 text-gray-300']">
              {{ intern.slot_utilization.percent }}%
            </span>
          </div>

          <!-- Slot Utilization Bar -->
          <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
              <span>Slot Usage</span>
              <span>{{ intern.slot_utilization.used }}/{{ intern.slot_utilization.max }}</span>
            </div>
            <div class="h-2 bg-dark-700 rounded-full overflow-hidden">
              <div 
                :class="['h-full transition-all', getUtilizationBarColor(intern.slot_utilization.percent)]"
                :style="{ width: Math.min(intern.slot_utilization.percent, 100) + '%' }">
              </div>
            </div>
          </div>

          <div class="flex justify-between items-center text-sm">
            <span class="text-gray-400">Active Quests: {{ intern.active_assignments }}</span>
            <span class="text-orange-400">🔥 {{ intern.current_streak }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="dashboardData.length === 0 && !isLoading" 
      class="text-center py-12 text-gray-500">
      No intern data available.
    </div>
  </div>
</template>
