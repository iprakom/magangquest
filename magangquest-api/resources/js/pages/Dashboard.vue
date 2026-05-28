<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Dashboard</h1>
            <p class="text-slate-400 text-sm">Welcome back, {{ userStore.user?.name || 'Adventurer' }}</p>
          </div>
          <div class="flex items-center gap-4">
            <NyawaDisplay
              v-if="endDate"
              :end-date="endDate"
              :holidays="holidays"
              :is-grace-period="isGracePeriod"
              :grace-started-at="userStore.user?.grace_period_started_at"
              :grace-penalty="gracePenalty"
            />
            <StreakBadge :streak="userStore.streak || 0" />
            <LevelBadge :level="userStore.level" />
            <!-- Mentor Dashboard Link -->
            <button
              v-if="userStore.isMentor || userStore.isAdmin"
              @click="$inertia.visit('/mentor/dashboard')"
              class="px-3 py-1.5 bg-purple-600/20 text-purple-400 rounded-lg hover:bg-purple-600/30 transition-colors text-sm font-medium"
            >
              🎓 Mentor Dashboard
            </button>
            <!-- Admin Navigation -->
            <div v-if="userStore.isAdmin" class="flex items-center gap-2">
              <button
                @click="$inertia.visit('/admin/onboarding')"
                class="px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm font-medium"
              >
                👥 Onboarding
              </button>
              <button
                @click="$inertia.visit('/admin/leaderboard')"
                class="px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm font-medium"
              >
                🏆 Leaderboard
              </button>
              <button
                @click="$inertia.visit('/admin/holidays')"
                class="px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm font-medium"
              >
                📅 Libur
              </button>
              <button
                @click="$inertia.visit('/admin/settings')"
                class="px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm font-medium"
              >
                ⚙️ Settings
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Critical Zone Warning Banner -->
      <div
        v-if="isCriticalZone"
        class="mb-6 bg-gradient-to-r from-red-900/80 to-red-800/80 backdrop-blur-sm rounded-xl border border-red-500/50 p-4"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-3xl">🚨</span>
            <div>
              <h3 class="text-lg font-bold text-red-200">FASE KRUSIAL</h3>
              <p class="text-red-300 text-sm">Tidak bisa klaim task baru. Selesaikan task yang tersisa!</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-white">{{ workingDaysRemaining }}</p>
            <p class="text-red-300 text-sm">hari kerja tersisa</p>
          </div>
        </div>
        <!-- Remaining Quests Count -->
        <div class="mt-3 pt-3 border-t border-red-500/30">
          <p class="text-red-200 text-sm">
            📋 <strong>{{ remainingQuestsCount }}</strong> task masih perlu diselesaikan
          </p>
        </div>
      </div>

      <!-- Grace Period Banner -->
      <div
        v-if="isGracePeriod"
        class="mb-6 bg-gradient-to-r from-orange-900/80 to-orange-800/80 backdrop-blur-sm rounded-xl border border-orange-500/50 p-4"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-3xl">⚠️</span>
            <div>
              <h3 class="text-lg font-bold text-orange-200">MASA TENGGANG</h3>
              <p class="text-orange-300 text-sm">Masih ada task yang harus diselesaikan. Penalty -10 poin/hari!</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-white">H+{{ graceDays }}</p>
            <p class="text-orange-300 text-sm">hari masa tenggang</p>
          </div>
        </div>
        <!-- Force Close Countdown -->
        <div class="mt-3 pt-3 border-t border-orange-500/30">
          <div class="flex items-center justify-between">
            <p class="text-orange-200 text-sm">
              📋 <strong>{{ remainingQuestsCount }}</strong> task masih perlu diselesaikan
            </p>
            <p class="text-orange-300 text-sm">
              ⏱️ Force Close dalam <strong>{{ forceCloseCountdown }}</strong> hari kerja
            </p>
          </div>
          <p class="text-orange-200 text-sm mt-1">
            💸 Total penalty: <strong>{{ gracePenalty }} poin</strong>
          </p>
        </div>
      </div>

      <!-- Graduation Bonus Banner -->
      <div
        v-if="showGraduationBonus"
        class="mb-6 bg-gradient-to-r from-amber-900/80 to-amber-800/80 backdrop-blur-sm rounded-xl border border-amber-500/50 p-4"
      >
        <div class="flex items-center gap-3">
          <span class="text-3xl">🎓</span>
          <div>
            <h3 class="text-lg font-bold text-amber-200">GRADUATION BONUS!</h3>
            <p class="text-amber-300 text-sm">+200 XP bonus karena tidak ada task aktif saat H-0!</p>
          </div>
        </div>
      </div>

      <!-- Perfect Day Banner -->
      <PerfectDayBanner
        v-if="showPerfectDay"
        :bonus-xp="15"
        @dismiss="showPerfectDay = false"
      />

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- XP Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-3xl">⚡</span>
            <span class="text-green-400 text-sm font-medium">+{{ recentXP }} this week</span>
          </div>
          <XpBar
            :current-xp="userStore.currentXP"
            :level="userStore.level"
          />
        </div>

        <!-- Completed Quests Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-3xl">✅</span>
            <span class="text-blue-400 text-sm font-medium">This month</span>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ stats.completed_quests || 0 }}</p>
          <p class="text-slate-400 text-sm">Quests Completed</p>
        </div>

        <!-- Pending Review Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-3xl">🔍</span>
            <span class="text-yellow-400 text-sm font-medium">In review</span>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ stats.pending_review || 0 }}</p>
          <p class="text-slate-400 text-sm">Pending Review</p>
        </div>

        <!-- Perfect Days Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-3xl">🏆</span>
            <span class="text-amber-400 text-sm font-medium">Total</span>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ userStore.perfectDays || 0 }}</p>
          <p class="text-slate-400 text-sm">Perfect Days</p>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Active Quests (2/3 width) -->
        <div class="lg:col-span-2">
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700">
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-white">Active Quests</h2>
              <button
                @click="$inertia.visit('/quest-logbook')"
                class="text-sm text-blue-400 hover:text-blue-300"
              >
                View All →
              </button>
            </div>

            <div v-if="loadingActive" class="p-6 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            </div>

            <div v-else-if="activeAssignments.length === 0" class="p-6 text-center">
              <span class="text-4xl mb-3 block">🎯</span>
              <p class="text-slate-400">No active quests. Accept a quest from the board!</p>
              <button
                @click="$inertia.visit('/quests')"
                class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                Browse Quests
              </button>
            </div>

            <div v-else class="divide-y divide-slate-700">
              <div
                v-for="assignment in activeAssignments.slice(0, 5)"
                :key="assignment.id"
                class="p-4 hover:bg-slate-700/30 transition-colors"
              >
                <div class="flex items-start gap-4">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <PriorityBadge :priority="assignment.quest?.priority || 'mid'" />
                      <StatusBadge :status="assignment.status" />
                    </div>
                    <h3 class="text-white font-medium">{{ assignment.quest?.title || 'Unknown Quest' }}</h3>
                    <p class="text-slate-400 text-sm mt-1">
                      {{ assignment.quest?.description?.substring(0, 80) || '' }}...
                    </p>
                    <div class="flex items-center gap-4 mt-2 text-sm">
                      <span class="text-slate-500">
                        ⚡ {{ getSlotWeight(assignment.quest?.priority) }} XP
                      </span>
                      <span v-if="assignment.quest?.due_date" class="text-slate-500">
                        📅 {{ formatDate(assignment.quest.due_date) }}
                      </span>
                    </div>
                  </div>
                  <button
                    @click="$inertia.visit(`/quests/${assignment.quest_id}`)"
                    class="px-3 py-1.5 text-sm bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors"
                  >
                    Details
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="mt-6 bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700">
            <div class="px-6 py-4 border-b border-slate-700">
              <h2 class="text-lg font-semibold text-white">Recent Activity</h2>
            </div>

            <div v-if="loadingTransactions" class="p-6 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            </div>

            <div v-else-if="recentTransactions.length === 0" class="p-6 text-center">
              <p class="text-slate-400">No recent activity</p>
            </div>

            <div v-else class="divide-y divide-slate-700">
              <div
                v-for="tx in recentTransactions"
                :key="tx.id"
                class="px-6 py-3 flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <span class="text-lg">{{ getTransactionIcon(tx.type) }}</span>
                  <div>
                    <p class="text-white text-sm">{{ tx.description || tx.type }}</p>
                    <p class="text-slate-500 text-xs">{{ formatDateTime(tx.created_at) }}</p>
                  </div>
                </div>
                <span :class="getTransactionClass(tx.type)">
                  {{ tx.xp_change > 0 ? '+' : '' }}{{ tx.xp_change }} XP
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar (1/3 width) -->
        <div class="space-y-6">
          <!-- Quick Stats -->
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
            <h3 class="text-white font-semibold mb-4">Quick Stats</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Total XP</span>
                <span class="text-white font-semibold">{{ userStore.currentXP.toLocaleString() }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Current Level</span>
                <span class="text-white font-semibold">Level {{ userStore.level }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Current Streak</span>
                <span class="text-orange-400 font-semibold">{{ userStore.streak || 0 }} days</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">WIP Slots</span>
                <span class="text-white font-semibold">
                  {{ questStore.wipSlots.used }} / {{ questStore.wipSlots.max }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Days Remaining</span>
                <span :class="userStore.isInCriticalZone ? 'text-red-400' : 'text-white'" class="font-semibold">
                  {{ userStore.daysRemaining ?? '∞' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Upcoming Deadlines -->
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700">
            <div class="px-6 py-4 border-b border-slate-700">
              <h3 class="text-white font-semibold">Upcoming Deadlines</h3>
            </div>

            <div v-if="upcomingDeadlines.length === 0" class="p-6 text-center">
              <p class="text-slate-400 text-sm">No upcoming deadlines</p>
            </div>

            <div v-else class="divide-y divide-slate-700">
              <div
                v-for="assignment in upcomingDeadlines"
                :key="assignment.id"
                class="px-6 py-3"
              >
                <div class="flex items-center justify-between mb-1">
                  <span class="text-white text-sm font-medium truncate max-w-[180px]">
                    {{ assignment.quest?.title }}
                  </span>
                  <span
                    :class="isUrgent(assignment.quest?.due_date) ? 'text-red-400' : 'text-slate-400'"
                    class="text-xs"
                  >
                    {{ formatDate(assignment.quest?.due_date) }}
                  </span>
                </div>
                <StatusBadge :status="assignment.status" />
              </div>
            </div>
          </div>

          <!-- Leaderboard Preview -->
          <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700">
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
              <h3 class="text-white font-semibold">Top Players</h3>
              <button
                @click="$inertia.visit('/leaderboard')"
                class="text-sm text-blue-400 hover:text-blue-300"
              >
                View All →
              </button>
            </div>

            <div v-if="leaderboardLoading" class="p-6 text-center">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500 mx-auto"></div>
            </div>

            <div v-else class="divide-y divide-slate-700">
              <div
                v-for="player in leaderboardStore.topPlayers.slice(0, 5)"
                :key="player.user_id"
                class="px-6 py-3 flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <span class="w-6 text-center" :class="getRankClass(player.rank)">
                    {{ player.rank <= 3 ? ['🥇', '🥈', '🥉'][player.rank - 1] : `#${player.rank}` }}
                  </span>
                  <span class="text-white text-sm">{{ player.name }}</span>
                </div>
                <span class="text-slate-400 text-sm">{{ player.xp.toLocaleString() }} XP</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { useQuestStore } from '@/stores/quest'
import { useLeaderboardStore } from '@/stores/leaderboard'
import XpBar from '@/components/XpBar.vue'
import StreakBadge from '@/components/StreakBadge.vue'
import LevelBadge from '@/components/LevelBadge.vue'
import PriorityBadge from '@/components/PriorityBadge.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import PerfectDayBanner from '@/components/PerfectDayBanner.vue'
import NyawaDisplay from '@/components/NyawaDisplay.vue'

const userStore = useUserStore()
const questStore = useQuestStore()
const leaderboardStore = useLeaderboardStore()

const props = defineProps({
  endDate: {
    type: String,
    default: null,
  },
  holidays: {
    type: Array,
    default: () => [],
  },
})

const loadingActive = ref(false)
const loadingTransactions = ref(false)
const leaderboardLoading = ref(false)
const recentTransactions = ref([])
const recentXP = ref(0)
const showPerfectDay = ref(false)

const stats = ref({
  completed_quests: 0,
  pending_review: 0,
})

// Holidays from page props (default to empty array)
const holidays = computed(() => Array.isArray(props.holidays) ? props.holidays : [])
function calculateWorkingDaysRemaining() {
  if (!props.endDate) return null

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const end = new Date(props.endDate)
  end.setHours(0, 0, 0, 0)

  // If end date is in the past
  if (end < today) {
    return calculateWorkingDaysPast(today, end)
  }

  let count = 0
  const current = new Date(today)
  // Start from tomorrow since today doesn't count as a full working day remaining
  current.setDate(current.getDate() + 1)

  const holidayDates = (holidays.value || []).map(h => {
    const d = new Date(h.date)
    d.setHours(0, 0, 0, 0)
    return d.getTime()
  })

  while (current <= end) {
    const dayOfWeek = current.getDay()
    const currentTime = current.getTime()

    // Check if it's a weekday (1-5 = Monday-Friday)
    const isWeekday = dayOfWeek >= 1 && dayOfWeek <= 5
    // Check if it's not a holiday
    const isNotHoliday = !holidayDates.includes(currentTime)

    if (isWeekday && isNotHoliday) {
      count++
    }
    current.setDate(current.getDate() + 1)
  }

  return count
}

function calculateWorkingDaysPast(today, end) {
  let count = 0
  const current = new Date(end)
  current.setDate(current.getDate() + 1) // Start from day after end

  const holidayDates = (holidays.value || []).map(h => {
    const d = new Date(h.date)
    d.setHours(0, 0, 0, 0)
    return d.getTime()
  })

  while (current < today) {
    const dayOfWeek = current.getDay()
    const currentTime = current.getTime()

    const isWeekday = dayOfWeek >= 1 && dayOfWeek <= 5
    const isNotHoliday = !holidayDates.includes(currentTime)

    if (isWeekday && isNotHoliday) {
      count++
    }
    current.setDate(current.getDate() + 1)
  }

  return -count
}

const workingDaysRemaining = computed(() => {
  return calculateWorkingDaysRemaining()
})

const isCriticalZone = computed(() => {
  const h = workingDaysRemaining.value
  return h !== null && h <= 10 && h >= 0 && !isGracePeriod.value
})

const isGracePeriod = computed(() => {
  return userStore.user?.is_grace_period === true || userStore.user?.is_grace_period === 1
})

const graceDays = computed(() => {
  // Calculate days since grace started (H+1, H+2, etc.)
  const h = workingDaysRemaining.value
  if (h === null || h >= 0) return 1
  return Math.abs(h) + 1
})

const forceCloseCountdown = computed(() => {
  // Force close is at H+8, so if we're at H+3, countdown is 5
  return Math.max(0, 8 - graceDays.value)
})

const gracePenalty = computed(() => {
  // Sum up grace penalty transactions
  return recentTransactions.value
    .filter(tx => tx.type === 'debit' && tx.reference === 'grace_penalty')
    .reduce((sum, tx) => sum + tx.points, 0)
})

const remainingQuestsCount = computed(() => {
  return activeAssignments.value.length
})

const showGraduationBonus = computed(() => {
  const h = workingDaysRemaining.value
  return h !== null && h <= 0 && activeAssignments.value.length === 0
})

const activeAssignments = computed(() => {
  return questStore.assignments.filter(a =>
    ['assigned', 'active', 'in_review'].includes(a.status)
  )
})

const upcomingDeadlines = computed(() => {
  return questStore.assignments
    .filter(a => a.quest?.due_date && !['approved', 'cancelled', 'failed'].includes(a.status))
    .sort((a, b) => new Date(a.quest.due_date) - new Date(b.quest.due_date))
    .slice(0, 4)
})

const slotWeights = { high: 4, mid: 2, low: 1 }

function getSlotWeight(priority) {
  return slotWeights[priority] || 2
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function isUrgent(dateStr) {
  if (!dateStr) return false
  const days = Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24))
  return days <= 3 && days >= 0
}

function getRankClass(rank) {
  if (rank === 1) return 'text-amber-400'
  if (rank === 2) return 'text-slate-300'
  if (rank === 3) return 'text-amber-600'
  return 'text-slate-400'
}

function getTransactionIcon(type) {
  const icons = {
    quest_completed: '✅',
    quest_approved: '🏆',
    bonus_xp: '⚡',
    perfect_day: '🏆',
    quest_failed: '❌',
  }
  return icons[type] || '📋'
}

function getTransactionClass(type) {
  if (type.includes('failed') || type.includes('cancelled')) return 'text-red-400 text-sm font-medium'
  return 'text-green-400 text-sm font-medium'
}

onMounted(async () => {
  loadingActive.value = true
  loadingTransactions.value = true
  leaderboardLoading.value = true

  try {
    await Promise.all([
      userStore.fetchUser(),
      userStore.fetchProfileStats(),
      questStore.fetchAssignments(),
      leaderboardStore.fetchLeaderboard(),
    ])

    // Fetch recent transactions
    try {
      recentTransactions.value = await userStore.fetchPointTransactions(5)
      const weekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000
      recentXP.value = recentTransactions.value
        .filter(tx => new Date(tx.created_at).getTime() > weekAgo)
        .reduce((sum, tx) => sum + (tx.xp_change > 0 ? tx.xp_change : 0), 0)
    } catch (e) {
      console.warn('Could not fetch transactions:', e)
    }

    // Calculate stats from assignments
    const all = questStore.assignments
    stats.value.completed_quests = all.filter(a => a.status === 'approved').length
    stats.value.pending_review = all.filter(a => a.status === 'in_review').length

    // Check if it's a perfect day (all active quests completed on time)
    if (stats.value.completed_quests > 0 && userStore.perfectDays > 0) {
      showPerfectDay.value = true
    }
  } catch (error) {
    console.error('Dashboard load error:', error)
  } finally {
    loadingActive.value = false
    loadingTransactions.value = false
    leaderboardLoading.value = false
  }
})
</script>
