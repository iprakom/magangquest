<script setup>
import { onMounted, computed, ref } from 'vue'
import { useUserStore } from '../stores/user'
import { useQuestsStore } from '../stores/quests'
import PerfectDayBanner from '../components/PerfectDayBanner.vue'

const userStore = useUserStore()
const questsStore = useQuestsStore()

onMounted(() => { 
  userStore.fetchUser(); 
  questsStore.fetchQuests() 
})

// H-x countdown calculation
const daysRemaining = computed(() => {
  if (!userStore.user?.end_date) return null
  const endDate = new Date(userStore.user.end_date)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  endDate.setHours(0, 0, 0, 0)
  const diffTime = endDate.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
})

const countdownColor = computed(() => {
  if (daysRemaining.value === null) return 'text-gray-400'
  if (daysRemaining.value <= 2) return 'text-red-500' // H-2 to H-0
  if (daysRemaining.value <= 5) return 'text-yellow-500' // H-5 to H-3
  if (daysRemaining.value >= 6 && daysRemaining.value <= 10) return 'text-green-500' // H-10 to H-6
  return 'text-green-500'
})

const countdownBg = computed(() => {
  if (daysRemaining.value === null) return 'bg-dark-700'
  if (daysRemaining.value <= 2) return 'bg-red-900/30 border-red-700'
  if (daysRemaining.value <= 5) return 'bg-yellow-900/30 border-yellow-700'
  if (daysRemaining.value >= 6 && daysRemaining.value <= 10) return 'bg-green-900/30 border-green-700'
  return 'bg-green-900/30 border-green-700'
})

const isInCriticalZone = computed(() => {
  return daysRemaining.value !== null && daysRemaining.value >= 0 && daysRemaining.value <= 10
})

const isInGracePeriod = computed(() => {
  return userStore.user?.is_grace_period
})

const gracePeriodDaysLeft = computed(() => {
  if (!userStore.user?.grace_period_started_at) return null
  const startDate = new Date(userStore.user.grace_period_started_at)
  const today = new Date()
  const diffTime = today.getTime() - startDate.getTime()
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  return 7 - diffDays // Grace period is 7 days
})
</script>

<template>
  <div class="space-y-6">
    <PerfectDayBanner :show="userStore.user?.perfect_day_today" />
    
    <div class="bg-dark-800 rounded-xl p-6 border border-dark-700">
      <h2 class="text-2xl font-bold mb-1">Welcome back, {{ userStore.user?.name }}! ⚔️</h2>
      <p class="text-gray-400">Level {{ userStore.level }} • {{ userStore.user?.total_xp || 0 }} XP Total</p>
    </div>

    <!-- H-x Countdown Display -->
    <div v-if="daysRemaining !== null" :class="['rounded-xl p-6 border-2', countdownBg]">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold mb-1">
            <span v-if="isInGracePeriod">Grace Period</span>
            <span v-else-if="isInCriticalZone">Critical Zone!</span>
            <span v-else>Nyawa (Days Remaining)</span>
          </h3>
          <p v-if="isInCriticalZone && !isInGracePeriod" class="text-sm text-red-400 mb-2">
            ⚠️ Cannot claim new Bounty or Assigned quests
          </p>
          <p v-if="isInGracePeriod" class="text-sm text-yellow-400 mb-2">
            ⏰ Late Penalty: -10 points per day • Force Close in {{ gracePeriodDaysLeft }} days
          </p>
        </div>
        <div :class="['text-5xl font-bold', countdownColor]">
          H{{ daysRemaining >= 0 ? '-' : '+' }}{{ Math.abs(daysRemaining) }}
        </div>
      </div>
      <div v-if="daysRemaining === 0 && !isInGracePeriod" class="mt-3 text-center text-yellow-400 font-semibold">
        🎓 Graduation Day! Waiting for mentor validation...
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-dark-800 rounded-xl p-5 border border-dark-700 text-center">
        <div class="text-3xl mb-1">🔥</div>
        <div class="text-2xl font-bold">{{ userStore.user?.current_streak || 0 }}</div>
        <div class="text-sm text-gray-400">Day Streak</div>
      </div>
      <div class="bg-dark-800 rounded-xl p-5 border border-dark-700 text-center">
        <div class="text-3xl mb-1">📜</div>
        <div class="text-2xl font-bold">{{ questsStore.quests.filter(q => q.pivot?.status === 'completed').length }}</div>
        <div class="text-sm text-gray-400">Quests Completed</div>
      </div>
      <div class="bg-dark-800 rounded-xl p-5 border border-dark-700 text-center">
        <div class="text-3xl mb-1">💎</div>
        <div class="text-2xl font-bold">{{ userStore.user?.perfect_days || 0 }}</div>
        <div class="text-sm text-gray-400">Perfect Days</div>
      </div>
    </div>

    <div class="bg-dark-800 rounded-xl p-6 border border-dark-700">
      <h3 class="font-semibold mb-4">Recent Activity</h3>
      <div v-if="questsStore.quests.filter(q => q.pivot?.status === 'completed').length === 0" class="text-gray-500 text-sm">
        No completed quests yet. Start earning XP!
      </div>
      <div v-else class="space-y-3">
        <div v-for="quest in questsStore.quests.filter(q => q.pivot?.status === 'completed').slice(0, 5)" :key="quest.id"
          class="flex justify-between items-center py-2 border-b border-dark-700 last:border-0">
          <span class="text-sm">{{ quest.title }}</span>
          <span class="text-accent-gold text-sm">+{{ quest.xp_reward }} XP</span>
        </div>
      </div>
    </div>
  </div>
</template>
