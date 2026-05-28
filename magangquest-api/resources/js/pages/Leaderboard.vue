<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Leaderboard</h1>
            <p class="text-slate-400 text-sm">See how you rank among other adventurers</p>
          </div>
          <div class="flex items-center gap-4">
            <!-- Status Filter Tabs -->
            <div class="flex items-center gap-1 bg-slate-700/50 rounded-lg p-1">
              <button
                v-for="tab in statusTabs"
                :key="tab.value"
                @click="setStatusFilter(tab.value)"
                class="px-3 py-1.5 text-sm rounded-md transition-colors"
                :class="currentStatus === tab.value
                  ? 'bg-blue-600 text-white'
                  : 'text-slate-400 hover:text-white'"
              >
                {{ tab.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-900/50 border border-red-700 rounded-lg p-6 text-center">
        <p class="text-red-300">{{ error }}</p>
        <button @click="loadLeaderboard" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <template v-else>
        <!-- Statistics Section -->
        <div class="grid grid-cols-3 gap-4 mb-8">
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
            <p class="text-slate-400 text-xs uppercase">Total Intern</p>
            <p class="text-2xl font-bold text-white">{{ stats.total_intern || 0 }}</p>
          </div>
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
            <p class="text-slate-400 text-xs uppercase">Avg Points</p>
            <p class="text-2xl font-bold text-purple-400">{{ stats.average_points || 0 }}</p>
          </div>
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 text-center">
            <p class="text-slate-400 text-xs uppercase">Completion Rate</p>
            <p class="text-2xl font-bold text-green-400">{{ stats.completion_rate || 0 }}%</p>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name..."
            class="w-full px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Top 3 Podium -->
        <div v-if="topThree.length > 0" class="mb-8">
          <div class="flex items-end justify-center gap-4">
            <!-- 2nd Place -->
            <div v-if="topThree[1]" class="flex flex-col items-center">
              <div class="w-20 h-20 rounded-full bg-slate-600 border-4 border-slate-500 flex items-center justify-center text-2xl mb-2">
                {{ getInitials(topThree[1].name) }}
              </div>
              <p class="text-slate-400 font-medium">{{ topThree[1].name }}</p>
              <p class="text-slate-500 text-sm">{{ topThree[1].total_points?.toLocaleString() || 0 }} Poin</p>
              <div class="mt-2 px-4 py-1 bg-slate-600 rounded-b-lg text-2xl">🥈</div>
            </div>

            <!-- 1st Place -->
            <div v-if="topThree[0]" class="flex flex-col items-center">
              <div class="w-24 h-24 rounded-full bg-amber-500/20 border-4 border-amber-500 flex items-center justify-center text-3xl mb-2 shadow-lg shadow-amber-500/30">
                {{ getInitials(topThree[0].name) }}
              </div>
              <p class="text-white font-bold">{{ topThree[0].name }}</p>
              <p class="text-amber-400 text-sm">{{ topThree[0].total_points?.toLocaleString() || 0 }} Poin</p>
              <div class="mt-2 px-4 py-1 bg-amber-500 rounded-b-lg text-3xl">🥇</div>
            </div>

            <!-- 3rd Place -->
            <div v-if="topThree[2]" class="flex flex-col items-center">
              <div class="w-20 h-20 rounded-full bg-amber-700/30 border-4 border-amber-700 flex items-center justify-center text-2xl mb-2">
                {{ getInitials(topThree[2].name) }}
              </div>
              <p class="text-slate-400 font-medium">{{ topThree[2].name }}</p>
              <p class="text-slate-500 text-sm">{{ topThree[2].total_points?.toLocaleString() || 0 }} Poin</p>
              <div class="mt-2 px-4 py-1 bg-amber-700/50 rounded-b-lg text-2xl">🥉</div>
            </div>
          </div>
        </div>

        <!-- My Rank Card -->
        <div v-if="myRank" class="bg-gradient-to-r from-blue-900/50 to-purple-900/50 rounded-xl border border-blue-700/50 p-6 mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-600/30 border-2 border-blue-500 flex items-center justify-center">
                <span class="text-xl font-bold text-blue-400">#{{ myRank.rank }}</span>
              </div>
              <div>
                <p class="text-slate-400 text-sm">Your Rank</p>
                <p class="text-white font-semibold">{{ myRank.name }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-slate-400 text-sm">Total Poin</p>
              <p class="text-2xl font-bold text-blue-400">{{ myRank.total_points?.toLocaleString() || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Rankings List -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
          <div class="px-6 py-3 border-b border-slate-700 flex items-center justify-between text-sm text-slate-400">
            <span>Rank</span>
            <span class="flex-1 px-4">Player</span>
            <span class="w-20 text-right">Poin</span>
            <span class="w-16 text-center">Streak</span>
          </div>

          <div v-if="rankings.length === 0" class="p-12 text-center">
            <span class="text-4xl mb-3 block">🏆</span>
            <p class="text-slate-400">No rankings available yet</p>
          </div>

          <div v-else>
            <div
              v-for="player in paginatedRankings"
              :key="player.id"
              class="px-6 py-4 flex items-center justify-between border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors"
              :class="{ 'bg-blue-900/20': player.is_current_user }"
            >
              <div class="flex items-center gap-4">
                <div class="w-8 text-center">
                  <span v-if="player.rank <= 3" class="text-xl">
                    {{ ['🥇', '🥈', '🥉'][player.rank - 1] }}
                  </span>
                  <span v-else class="text-slate-400 font-medium">#{{ player.rank }}</span>
                </div>

                <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-sm font-medium text-slate-300">
                  {{ getInitials(player.name) }}
                </div>

                <div>
                  <p class="text-white font-medium">
                    {{ player.name }}
                    <span v-if="player.is_current_user" class="ml-2 px-2 py-0.5 text-xs bg-blue-600/30 text-blue-400 rounded-full">You</span>
                  </p>
                  <p v-if="player.email" class="text-slate-500 text-xs">{{ player.email }}</p>
                </div>
              </div>

              <div class="flex items-center gap-6">
                <div class="text-right">
                  <p class="text-white font-medium">{{ (player.total_points || 0).toLocaleString() }}</p>
                  <p class="text-slate-500 text-xs">Poin</p>
                </div>
                <div class="w-12 text-center">
                  <span class="text-orange-400">🔥 {{ player.current_streak || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Export Button -->
        <div class="mt-6 flex justify-end">
          <button
            @click="exportCsv"
            :disabled="exporting"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-medium"
          >
            📥 Export CSV
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-6 flex justify-center gap-2">
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
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)
const error = ref(null)
const exporting = ref(false)
const rankings = ref([])
const stats = ref({})
const currentStatus = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = 20
const pagination = ref({ limit: 50, offset: 0, total: 0 })

const statusTabs = [
  { label: 'Semua', value: 'all' },
  { label: 'Aktif', value: 'active' },
  { label: 'Lulus', value: 'graduated' },
  { label: 'Frozen', value: 'frozen' },
]

const topThree = computed(() => rankings.value.slice(0, 3))

const totalPages = computed(() => Math.ceil(rankings.value.length / perPage))

const paginatedRankings = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return rankings.value.slice(start, start + perPage)
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

const myRank = computed(() => {
  return rankings.value.find(r => r.is_current_user)
})

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

function setStatusFilter(status) {
  currentStatus.value = status
  currentPage.value = 1
  fetchLeaderboard()
}

let searchTimeout = null
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchLeaderboard()
  }, 300)
}

async function fetchLeaderboard() {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams()
    params.append('status', currentStatus.value)
    params.append('limit', 100)
    if (searchQuery.value) params.append('search', searchQuery.value)

    const response = await fetch(`/api/leaderboard?${params.toString()}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      }
    })
    const data = await response.json()

    if (data.leaderboard) {
      // Mark current user's rank
      const currentUserId = getCurrentUserId()
      rankings.value = data.leaderboard.map((user, index) => ({
        ...user,
        rank: index + 1,
        is_current_user: user.id === currentUserId,
        total_points: user.total_points || 0,
        current_streak: user.current_streak || 0,
      }))
      pagination.value = data.pagination
    } else {
      error.value = 'Failed to load leaderboard'
    }
  } catch (e) {
    error.value = 'Failed to load leaderboard'
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
    // Silently fail for stats
  }
}

function getCurrentUserId() {
  // Try to get from meta tag or localStorage
  const metaUser = document.querySelector('meta[name="user-id"]')
  if (metaUser) return parseInt(metaUser.content)

  // Try from Inertia page props
  const pageEl = document.querySelector('[data-page]')
  if (pageEl) {
    try {
      const pageData = JSON.parse(pageEl.dataset.page)
      return pageData.props?.auth?.user?.id
    } catch (e) {}
  }
  return null
}

async function exportCsv() {
  exporting.value = true

  try {
    const params = new URLSearchParams()
    params.append('status', currentStatus.value)

    const response = await fetch(`/api/leaderboard/export?${params.toString()}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'text/csv',
      }
    })

    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `leaderboard_${new Date().toISOString().slice(0, 10)}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
    }
  } catch (e) {
    alert('Failed to export CSV')
  } finally {
    exporting.value = false
  }
}

function loadLeaderboard() {
  fetchLeaderboard()
}

onMounted(() => {
  loadLeaderboard()
  fetchStats()
})
</script>
