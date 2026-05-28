<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Mentor Dashboard</h1>
            <p class="text-slate-400 text-sm">Monitor intern slot utilization by room</p>
          </div>
          <div class="flex items-center gap-4">
            <button
              @click="refreshData"
              :disabled="loading"
              class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors flex items-center gap-2"
            >
              <span :class="{ 'animate-spin': loading }">🔄</span>
              Refresh
            </button>
            <span class="text-slate-400 text-sm">
              Last updated: {{ lastUpdated ? formatTime(lastUpdated) : 'Never' }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">👥</span>
            <span class="text-slate-400 text-sm">Total</span>
          </div>
          <p class="text-4xl font-bold text-white">{{ summary.total_interns }}</p>
          <p class="text-slate-400 text-sm">Interns</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-red-700/50 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">🔴</span>
            <span class="text-red-400 text-sm">Idle</span>
          </div>
          <p class="text-4xl font-bold text-red-400">{{ summary.idle_count }}</p>
          <p class="text-slate-400 text-sm">Utilization &lt;=50%</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-yellow-700/50 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">🟡</span>
            <span class="text-yellow-400 text-sm">Active</span>
          </div>
          <p class="text-4xl font-bold text-yellow-400">{{ summary.optimal_count }}</p>
          <p class="text-slate-400 text-sm">Utilization 51-99%</p>
        </div>

        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-gray-600 p-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-3xl">⚫</span>
            <span class="text-gray-400 text-sm">Full</span>
          </div>
          <p class="text-4xl font-bold text-gray-400">{{ summary.overloaded_count }}</p>
          <p class="text-slate-400 text-sm">Utilization &gt;=100%</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-slate-400 mb-1">Filter by Room</label>
            <select
              v-model="filters.room"
              @change="applyFilters"
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All Rooms</option>
              <option v-for="room in availableRooms" :key="room" :value="room">{{ room }}</option>
            </select>
          </div>

          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-slate-400 mb-1">Filter by Status</label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">All Status</option>
              <option value="idle">Idle (Red)</option>
              <option value="optimal">Active (Yellow)</option>
              <option value="overloaded">Overloaded (Gray)</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button
              @click="clearFilters"
              class="px-4 py-2 text-slate-300 bg-slate-700 rounded-md hover:bg-slate-600 transition-colors"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-900/50 border border-red-700 rounded-lg p-6 mb-6">
        <p class="text-red-300">{{ error }}</p>
        <button @click="fetchDashboard" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <!-- Room Groups -->
      <div v-else>
        <div v-if="filteredGroupedData.length === 0" class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-12 text-center">
          <span class="text-6xl mb-4 block">📊</span>
          <h3 class="text-lg font-medium text-white mb-2">No interns found</h3>
          <p class="text-slate-400">Try adjusting your filters or check back later.</p>
        </div>

        <div v-for="roomData in filteredGroupedData" :key="roomData.room" class="mb-8">
          <!-- Room Header -->
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
              <span>🏢</span>
              {{ roomData.room }}
              <span class="text-slate-400 text-sm font-normal">({{ roomData.interns.length }} interns)</span>
            </h2>
            <div class="flex items-center gap-4 text-sm">
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span class="text-slate-400">{{ roomData.idleCount }} Idle</span>
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span class="text-slate-400">{{ roomData.activeCount }} Active</span>
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                <span class="text-slate-400">{{ roomData.overloadedCount }} Full</span>
              </span>
            </div>
          </div>

          <!-- Interns Table -->
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
            <table class="w-full">
              <thead class="bg-slate-700/50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Intern</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Slot Usage</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Active Assignments</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Streak</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Endgame Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700">
                <tr
                  v-for="intern in roomData.interns"
                  :key="intern.user_id"
                  class="hover:bg-slate-700/30 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 font-medium">
                        {{ getInitials(intern.name) }}
                      </div>
                      <div>
                        <p class="text-white font-medium">{{ intern.name }}</p>
                        <p class="text-slate-400 text-sm">{{ intern.email }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="w-32 h-2 bg-slate-700 rounded-full overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all duration-300"
                          :class="getStatusBarClass(intern.status_color)"
                          :style="{ width: `${Math.min(intern.slot_utilization.percent, 100)}%` }"
                        ></div>
                      </div>
                      <span class="text-white text-sm font-medium">
                        {{ intern.slot_utilization.used }} / {{ intern.slot_utilization.max }} slots
                      </span>
                      <span class="text-slate-400 text-sm">
                        ({{ intern.slot_utilization.percent }}%)
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                      :class="getStatusBadgeClass(intern.status_color)"
                    >
                      {{ getStatusLabel(intern.status, intern.status_color) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-slate-400">
                    {{ intern.active_assignments }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-orange-400 font-medium">
                      🔥 {{ intern.current_streak }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      v-if="intern.is_grace_period"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30"
                    >
                      ⚠️ Masa Tenggang
                    </span>
                    <span
                      v-else-if="intern.is_critical_zone"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30"
                    >
                      🚨 Fase Krusial
                    </span>
                    <span
                      v-else-if="intern.working_days_remaining !== null && intern.working_days_remaining <= 0"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30"
                    >
                      🎓 Lulus
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-500/20 text-slate-400 border border-slate-500/30"
                    >
                      ✓ Normal
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const loading = ref(false)
const error = ref(null)
const lastUpdated = ref(null)
const dashboardData = ref([])
const groupedData = ref([])

const filters = ref({
  room: '',
  status: '',
})

// Auto-refresh interval (5 minutes)
const REFRESH_INTERVAL = 5 * 60 * 1000
let refreshTimer = null

const summary = computed(() => {
  return {
    total_interns: dashboardData.value.length,
    idle_count: dashboardData.value.filter(i => i.status === 'idle').length,
    optimal_count: dashboardData.value.filter(i => i.status === 'optimal').length,
    overloaded_count: dashboardData.value.filter(i => i.status === 'overloaded').length,
  }
})

const availableRooms = computed(() => {
  const rooms = new Set(dashboardData.value.map(i => i.room))
  return Array.from(rooms).sort()
})

const filteredGroupedData = computed(() => {
  let data = groupedData.value

  // Filter by room
  if (filters.value.room) {
    data = data.filter(rd => rd.room === filters.value.room)
  }

  // Filter by status
  if (filters.value.status) {
    data = data.map(rd => ({
      ...rd,
      interns: rd.interns.filter(i => i.status === filters.value.status),
    })).filter(rd => rd.interns.length > 0)
  }

  return data
})

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

function getStatusBarClass(statusColor) {
  switch (statusColor) {
    case 'red': return 'bg-red-500'
    case 'yellow': return 'bg-yellow-500'
    case 'gray': return 'bg-gray-500'
    default: return 'bg-slate-500'
  }
}

function getStatusBadgeClass(statusColor) {
  switch (statusColor) {
    case 'red': return 'bg-red-500/20 text-red-400 border border-red-500/30'
    case 'yellow': return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'
    case 'gray': return 'bg-gray-500/20 text-gray-400 border border-gray-500/30'
    default: return 'bg-slate-500/20 text-slate-400 border border-slate-500/30'
  }
}

function getStatusLabel(status, statusColor) {
  if (status === 'idle' || statusColor === 'red') return '🔴 Idle'
  if (status === 'optimal' || statusColor === 'yellow') return '🟡 Active'
  if (status === 'overloaded' || statusColor === 'gray') return '⚫ Full'
  return status
}

function formatTime(date) {
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function fetchDashboard() {
  loading.value = true
  error.value = null

  try {
    const response = await axios.get('/api/mentor/idle-dashboard', {
      params: {
        room: filters.value.room || undefined,
      },
    })

    dashboardData.value = response.data.dashboard
    groupedData.value = Object.entries(response.data.by_room || {}).map(([room, interns]) => ({
      room,
      interns,
      idleCount: interns.filter(i => i.status === 'idle').length,
      activeCount: interns.filter(i => i.status === 'optimal').length,
      overloadedCount: interns.filter(i => i.status === 'overloaded').length,
    })).sort((a, b) => a.room.localeCompare(b.room))

    lastUpdated.value = new Date()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch dashboard data'
    console.error('Dashboard fetch error:', err)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchDashboard()
}

function clearFilters() {
  filters.value = {
    room: '',
    status: '',
  }
  fetchDashboard()
}

function refreshData() {
  fetchDashboard()
}

onMounted(() => {
  fetchDashboard()

  // Set up auto-refresh
  refreshTimer = setInterval(() => {
    fetchDashboard()
  }, REFRESH_INTERVAL)
})

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})
</script>
