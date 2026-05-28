<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Leaderboard</h1>
            <p class="text-gray-500 text-sm">Kelola dan export laporan akhir peserta magang</p>
          </div>
          <button
            @click="exportCsv"
            :disabled="exporting"
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            <span v-if="exporting">Mengeksport...</span>
            <span v-else>📥 Export CSV</span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Statistics Dashboard -->
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <!-- Total Intern -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Total Intern</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total_intern || 0 }}</p>
        </div>
        <!-- Active -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Aktif</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.active_intern || 0 }}</p>
        </div>
        <!-- Graduated -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Lulus</p>
          <p class="text-2xl font-bold text-blue-600">{{ stats.graduated_intern || 0 }}</p>
        </div>
        <!-- Frozen -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Frozen</p>
          <p class="text-2xl font-bold text-red-600">{{ stats.frozen_intern || 0 }}</p>
        </div>
        <!-- Avg Points -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Rata-rata Poin</p>
          <p class="text-2xl font-bold text-purple-600">{{ stats.average_points || 0 }}</p>
        </div>
        <!-- Completion Rate -->
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-gray-500 text-xs font-medium uppercase">Completion Rate</p>
          <p class="text-2xl font-bold text-indigo-600">{{ stats.completion_rate || 0 }}%</p>
        </div>
      </div>

      <!-- Top Performer Card -->
      <div v-if="stats.top_performer" class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-amber-100 text-sm font-medium">🏆 Top Performer</p>
            <p class="text-2xl font-bold">{{ stats.top_performer.name }}</p>
            <p class="text-amber-100 text-sm">{{ stats.top_performer.email }}</p>
          </div>
          <div class="text-right">
            <p class="text-4xl font-bold">{{ stats.top_performer.total_points || 0 }}</p>
            <p class="text-amber-100 text-sm">Total Poin</p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200">
          <div class="flex flex-wrap items-center gap-4">
            <!-- Status Filter Tabs -->
            <div class="flex border-b border-gray-200 -mb-4">
              <button
                v-for="tab in statusTabs"
                :key="tab.value"
                @click="setStatusFilter(tab.value)"
                :class="[
                  'px-4 py-3 text-sm font-medium border-b-2 -mb-px',
                  currentStatus === tab.value
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                ]"
              >
                {{ tab.label }}
                <span v-if="tab.count !== undefined" class="ml-1 text-xs">({{ tab.count }})</span>
              </button>
            </div>

            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama atau email..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @input="debouncedSearch"
              />
            </div>

            <!-- Batch Filter -->
            <div>
              <input
                v-model="batchFilter"
                type="text"
                placeholder="Filter batch (cth: 2025)"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                @input="debouncedSearch"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Leaderboard Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Poin</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Streak</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="loading" class="text-center py-8">
                <td colspan="7" class="px-4 py-8 text-gray-500">Memuat data...</td>
              </tr>
              <tr v-else-if="leaderboard.length === 0" class="text-center py-8">
                <td colspan="7" class="px-4 py-8 text-gray-500">Tidak ada data leaderboard</td>
              </tr>
              <tr
                v-else
                v-for="user in leaderboard"
                :key="user.id"
                class="hover:bg-gray-50"
              >
                <td class="px-4 py-3">
                  <span
                    v-if="user.rank <= 3"
                    class="text-2xl"
                  >
                    {{ ['🥇', '🥈', '🥉'][user.rank - 1] }}
                  </span>
                  <span v-else class="text-gray-500 font-medium">#{{ user.rank }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-xs text-gray-500">{{ user.intern_type || '-' }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ user.email }}</td>
                <td class="px-4 py-3">
                  <span class="text-lg font-bold text-purple-600">{{ user.total_points || 0 }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-1 text-orange-600">
                    🔥 {{ user.current_streak || 0 }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span class="text-green-600 font-medium">{{ user.completed_quests || 0 }}</span>
                  <span class="text-gray-400">/{{ user.total_quests || 0 }}</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="getStatusClass(user.onboarding_status)" class="px-2 py-1 rounded text-xs font-medium">
                    {{ formatStatus(user.onboarding_status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing {{ pagination.offset + 1 }} to {{ Math.min(pagination.offset + leaderboard.length, pagination.total) }} of {{ pagination.total }}
          </div>
          <div class="flex gap-2">
            <button
              @click="prevPage"
              :disabled="pagination.offset === 0"
              class="px-3 py-1 rounded bg-gray-100 text-gray-700 disabled:opacity-50 hover:bg-gray-200"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="pagination.offset + leaderboard.length >= pagination.total"
              class="px-3 py-1 rounded bg-gray-100 text-gray-700 disabled:opacity-50 hover:bg-gray-200"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)
const exporting = ref(false)
const error = ref(null)

const leaderboard = ref([])
const stats = ref({})
const pagination = ref({ limit: 50, offset: 0, total: 0 })
const currentStatus = ref('all')
const searchQuery = ref('')
const batchFilter = ref('')

const statusTabs = computed(() => [
  { label: 'Semua', value: 'all', count: stats.value.total_intern },
  { label: 'Aktif', value: 'active', count: stats.value.active_intern },
  { label: 'Lulus', value: 'graduated', count: stats.value.graduated_intern },
  { label: 'Frozen', value: 'frozen', count: stats.value.frozen_intern },
])

let searchTimeout = null

function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchLeaderboard()
  }, 300)
}

function setStatusFilter(status) {
  currentStatus.value = status
  pagination.value.offset = 0
  fetchLeaderboard()
}

function formatStatus(status) {
  const statusMap = {
    'restricted': 'Frozen',
    'pending': 'Pending',
    'active': 'Active',
    'frozen': 'Graduated',
  }
  return statusMap[status] || status
}

function getStatusClass(status) {
  const classes = {
    'restricted': 'bg-red-100 text-red-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'active': 'bg-green-100 text-green-800',
    'frozen': 'bg-blue-100 text-blue-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

async function fetchLeaderboard() {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams()
    params.append('status', currentStatus.value)
    params.append('limit', pagination.value.limit)
    params.append('offset', pagination.value.offset)
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (batchFilter.value) params.append('batch', batchFilter.value)

    const response = await fetch(`/api/admin/leaderboard?${params.toString()}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      }
    })
    const data = await response.json()

    if (data.success) {
      leaderboard.value = data.leaderboard
      pagination.value = data.pagination
    } else {
      error.value = data.message
    }
  } catch (e) {
    error.value = 'Gagal memuat leaderboard'
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const response = await fetch('/api/admin/stats', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      }
    })
    const data = await response.json()

    if (data.success) {
      stats.value = data.statistics
    }
  } catch (e) {
    console.error('Failed to fetch stats:', e)
  }
}

async function exportCsv() {
  exporting.value = true

  try {
    const params = new URLSearchParams()
    params.append('status', currentStatus.value)
    if (batchFilter.value) params.append('batch', batchFilter.value)

    const response = await fetch(`/api/admin/leaderboard/export?${params.toString()}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'text/csv',
      }
    })

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `leaderboard_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    alert('Gagal export CSV')
  } finally {
    exporting.value = false
  }
}

function prevPage() {
  if (pagination.value.offset > 0) {
    pagination.value.offset -= pagination.value.limit
    fetchLeaderboard()
  }
}

function nextPage() {
  if (pagination.value.offset + leaderboard.value.length < pagination.value.total) {
    pagination.value.offset += pagination.value.limit
    fetchLeaderboard()
  }
}

onMounted(() => {
  fetchLeaderboard()
  fetchStats()
})
</script>
