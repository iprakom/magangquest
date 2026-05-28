<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h1 class="text-2xl font-bold text-white">Profile</h1>
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
        <button @click="loadProfile" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <template v-else>
        <!-- Profile Header Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 mb-6 overflow-hidden">
          <!-- Banner Area -->
          <div class="h-32 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600"></div>

          <div class="px-6 pb-6">
            <!-- Avatar and Basic Info -->
            <div class="flex flex-col sm:flex-row items-center sm:items-end -mt-12 mb-6 gap-4">
              <div class="w-24 h-24 rounded-full bg-slate-700 border-4 border-slate-800 flex items-center justify-center text-4xl shadow-lg">
                {{ getInitials(user.name) }}
              </div>
              <div class="text-center sm:text-left flex-1">
                <h2 class="text-2xl font-bold text-white">{{ user.name }}</h2>
                <p class="text-slate-400">{{ user.email }}</p>
                <div class="flex items-center gap-3 mt-2 justify-center sm:justify-start">
                  <LevelBadge :level="stats.level" size="md" />
                  <StreakBadge :streak="streak" />
                </div>
              </div>
              <div class="text-center sm:text-right">
                <p class="text-slate-400 text-sm">Role</p>
                <span class="inline-block px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-sm font-medium capitalize">
                  {{ user.role }}
                </span>
              </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="bg-slate-700/50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-white">{{ stats.current_xp?.toLocaleString() || 0 }}</p>
                <p class="text-slate-400 text-sm">Total XP</p>
              </div>
              <div class="bg-slate-700/50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-white">{{ stats.level || 1 }}</p>
                <p class="text-slate-400 text-sm">Level</p>
              </div>
              <div class="bg-slate-700/50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-white">{{ completedQuests }}</p>
                <p class="text-slate-400 text-sm">Completed</p>
              </div>
              <div class="bg-slate-700/50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-orange-400">{{ streak }}</p>
                <p class="text-slate-400 text-sm">Day Streak</p>
              </div>
            </div>
          </div>
        </div>

        <!-- XP Progress Section -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 mb-6">
          <h3 class="text-white font-semibold mb-4">Experience Progress</h3>
          <XpBar :current-xp="stats.current_xp" :level="stats.level" />

          <div class="mt-4 grid grid-cols-2 gap-4 text-center">
            <div>
              <p class="text-slate-400 text-sm">Previous Level</p>
              <p class="text-white font-medium">Level {{ (stats.level || 1) - 1 }}</p>
            </div>
            <div>
              <p class="text-slate-400 text-sm">Next Level</p>
              <p class="text-white font-medium">Level {{ (stats.level || 1) + 1 }}</p>
            </div>
          </div>
        </div>

        <!-- Profile Details -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 mb-6">
          <h3 class="text-white font-semibold mb-4">Profile Details</h3>

          <div class="space-y-4">
            <div class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Full Name</span>
              <span class="text-white">{{ user.name }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Email</span>
              <span class="text-white">{{ user.email }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Role</span>
              <span class="text-white capitalize">{{ user.role }}</span>
            </div>
            <div v-if="user.department" class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Department</span>
              <span class="text-white">{{ user.department }}</span>
            </div>
            <div v-if="user.institution" class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Institution</span>
              <span class="text-white">{{ user.institution }}</span>
            </div>
            <div v-if="user.start_date" class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">Start Date</span>
              <span class="text-white">{{ formatDate(user.start_date) }}</span>
            </div>
            <div v-if="user.end_date" class="flex items-center justify-between py-2 border-b border-slate-700">
              <span class="text-slate-400">End Date</span>
              <span class="text-white">{{ formatDate(user.end_date) }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
              <span class="text-slate-400">Member Since</span>
              <span class="text-white">{{ user.created_at ? formatDate(user.created_at) : 'N/A' }}</span>
            </div>
          </div>
        </div>

        <!-- Quest Stats Breakdown -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 mb-6">
          <h3 class="text-white font-semibold mb-4">Quest Statistics</h3>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="text-center p-3 bg-slate-700/50 rounded-lg">
              <p class="text-2xl font-bold text-green-400">{{ questStats.approved }}</p>
              <p class="text-slate-400 text-sm">Approved</p>
            </div>
            <div class="text-center p-3 bg-slate-700/50 rounded-lg">
              <p class="text-2xl font-bold text-yellow-400">{{ questStats.in_review }}</p>
              <p class="text-slate-400 text-sm">In Review</p>
            </div>
            <div class="text-center p-3 bg-slate-700/50 rounded-lg">
              <p class="text-2xl font-bold text-blue-400">{{ questStats.active }}</p>
              <p class="text-slate-400 text-sm">Active</p>
            </div>
            <div class="text-center p-3 bg-slate-700/50 rounded-lg">
              <p class="text-2xl font-bold text-red-400">{{ questStats.failed }}</p>
              <p class="text-slate-400 text-sm">Failed</p>
            </div>
          </div>

          <!-- Success Rate -->
          <div class="mt-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-slate-400 text-sm">Success Rate</span>
              <span class="text-white font-medium">{{ successRate }}%</span>
            </div>
            <div class="w-full h-3 bg-slate-700 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="successRateClass"
                :style="{ width: `${successRate}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Perfect Days Section -->
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-white font-semibold">Perfect Days</h3>
              <p class="text-slate-400 text-sm mt-1">Complete all active quests on time</p>
            </div>
            <div class="text-center">
              <p class="text-4xl font-bold text-amber-400">{{ perfectDays }}</p>
              <p class="text-slate-400 text-sm">days</p>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { useQuestStore } from '@/stores/quest'
import XpBar from '@/components/XpBar.vue'
import StreakBadge from '@/components/StreakBadge.vue'
import LevelBadge from '@/components/LevelBadge.vue'

const userStore = useUserStore()
const questStore = useQuestStore()

const loading = ref(true)
const error = ref(null)
const perfectDays = ref(0)
const streak = ref(0)

const user = computed(() => userStore.user || {})
const stats = computed(() => userStore.stats || {})
const assignments = computed(() => questStore.assignments)

const completedQuests = computed(() => {
  return questStore.assignments.filter(a => a.status === 'approved').length
})

const questStats = computed(() => {
  const all = questStore.assignments
  return {
    approved: all.filter(a => a.status === 'approved').length,
    in_review: all.filter(a => a.status === 'in_review').length,
    active: all.filter(a => ['assigned', 'active'].includes(a.status)).length,
    failed: all.filter(a => ['failed', 'cancelled'].includes(a.status)).length,
  }
})

const successRate = computed(() => {
  const total = questStore.assignments.length
  if (total === 0) return 0
  const completed = questStats.value.approved + questStats.value.failed
  if (completed === 0) return 0
  return Math.round((questStats.value.approved / completed) * 100)
})

const successRateClass = computed(() => {
  if (successRate.value >= 80) return 'bg-green-500'
  if (successRate.value >= 60) return 'bg-yellow-500'
  return 'bg-red-500'
})

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

async function loadProfile() {
  loading.value = true
  error.value = null

  try {
    await Promise.all([
      userStore.fetchUser(),
      userStore.fetchProfileStats(),
      questStore.fetchAssignments(),
    ])

    streak.value = userStore.streak || 0
    perfectDays.value = userStore.perfectDays || 0
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load profile'
    console.error('Profile load error:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>
