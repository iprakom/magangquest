<script setup>
import { ref, onMounted } from 'vue'

const leaderboard = ref([])
const isLoading = ref(false)
const error = ref('')
const filters = ref({
  status: '',
  limit: 50
})
const pagination = ref({
  limit: 50,
  offset: 0
})

async function fetchLeaderboard() {
  isLoading.value = true
  error.value = ''
  
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    params.append('limit', filters.value.limit)
    
    const response = await fetch(`/api/leaderboard?${params}`)
    if (response.ok) {
      const data = await response.json()
      leaderboard.value = data.leaderboard
    } else {
      error.value = 'Failed to fetch leaderboard'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

async function exportLeaderboard(format = 'csv') {
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    params.append('format', format)
    
    const response = await fetch(`/api/leaderboard/export?${params}`)
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `leaderboard_${new Date().toISOString().split('T')[0]}.${format}`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      a.remove()
    } else {
      error.value = 'Export failed'
    }
  } catch (e) {
    error.value = 'Export failed. Please try again.'
  }
}

function getRankBadgeClass(rank) {
  if (rank === 1) return 'bg-yellow-500/20 text-yellow-400 border-yellow-500'
  if (rank === 2) return 'bg-gray-400/20 text-gray-300 border-gray-400'
  if (rank === 3) return 'bg-orange-600/20 text-orange-400 border-orange-600'
  return 'bg-dark-700 text-gray-400 border-dark-600'
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'active': return 'bg-green-900/30 text-green-400 border-green-700'
    case 'graduated': return 'bg-blue-900/30 text-blue-400 border-blue-700'
    case 'frozen': return 'bg-gray-900/30 text-gray-400 border-gray-700'
    default: return 'bg-dark-700 text-gray-400 border-dark-600'
  }
}

onMounted(() => {
  fetchLeaderboard()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">🏆 Leaderboard</h2>
      <div class="flex gap-2">
        <button @click="exportLeaderboard('csv')"
          class="bg-green-700 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
          📥 Export CSV
        </button>
        <button @click="fetchLeaderboard" :disabled="isLoading"
          class="bg-dark-700 hover:bg-dark-600 text-white py-2 px-4 rounded-lg transition-colors">
          {{ isLoading ? 'Loading...' : '🔄 Refresh' }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 flex flex-wrap gap-4 items-end">
      <div>
        <label class="block text-sm text-gray-400 mb-1">Status Filter</label>
        <select v-model="filters.status"
          class="bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white text-sm
            focus:border-accent-gold focus:outline-none">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="graduated">Graduated</option>
          <option value="frozen">Frozen</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Show Top</label>
        <select v-model="filters.limit"
          class="bg-dark-700 border border-dark-600 rounded-lg px-3 py-2 text-white text-sm
            focus:border-accent-gold focus:outline-none">
          <option :value="10">Top 10</option>
          <option :value="25">Top 25</option>
          <option :value="50">Top 50</option>
          <option :value="100">Top 100</option>
        </select>
      </div>
      <button @click="fetchLeaderboard"
        class="bg-accent-gold hover:bg-accent-gold/80 text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
        Apply
      </button>
    </div>

    <div v-if="error" class="bg-red-900/30 border border-red-700 rounded-lg p-4 text-red-400">
      {{ error }}
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-dark-700">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Rank</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Name</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Type</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Points</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Streak</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Completed</th>
              <th class="px-4 py-3 text-center text-sm font-semibold text-gray-300">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-dark-700">
            <tr v-for="user in leaderboard" :key="user.id" class="hover:bg-dark-700/50 transition-colors">
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 rounded border text-sm font-bold', getRankBadgeClass(user.rank)]">
                  #{{ user.rank }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="font-semibold text-white">{{ user.name }}</div>
                <div class="text-xs text-gray-400">{{ user.email }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-400">
                {{ user.intern_type || 'N/A' }}
              </td>
              <td class="px-4 py-3 text-right">
                <span class="text-accent-gold font-bold text-lg">{{ user.total_points || 0 }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="text-orange-400">🔥 {{ user.current_streak || 0 }}</span>
              </td>
              <td class="px-4 py-3 text-right text-gray-400">
                {{ user.completed_quests || 0 }}
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="['px-2 py-1 rounded border text-xs', getStatusBadgeClass(user.onboarding_status)]">
                  {{ user.onboarding_status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="leaderboard.length === 0 && !isLoading" class="text-center py-12 text-gray-500">
        No leaderboard data available.
      </div>
    </div>
  </div>
</template>
