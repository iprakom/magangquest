<script setup>
import { onMounted } from 'vue'
import { useUserStore } from '../stores/user'
import StreakBadge from '../components/StreakBadge.vue'

const userStore = useUserStore()
onMounted(() => userStore.fetchUser())
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <h2 class="text-2xl font-bold">👤 Profile</h2>

    <div v-if="userStore.user" class="space-y-6">
      <!-- User Info Card -->
      <div class="bg-dark-800 rounded-xl p-6 border border-dark-700">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 rounded-full bg-accent-emerald flex items-center justify-center text-2xl font-bold">
            {{ userStore.user.name?.charAt(0) }}
          </div>
          <div>
            <h3 class="text-xl font-bold">{{ userStore.user.name }}</h3>
            <p class="text-gray-400">{{ userStore.user.email }}</p>
            <span class="inline-block mt-1 px-2 py-0.5 bg-accent-emerald/20 text-accent-emerald text-xs rounded">
              {{ userStore.user.role?.display_name }}
            </span>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Department</span>
            <p>{{ userStore.user.department }}</p>
          </div>
          <div>
            <span class="text-gray-500">Level</span>
            <p class="font-bold">{{ userStore.level }}</p>
          </div>
        </div>
      </div>

      <!-- XP & Streak -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-dark-800 rounded-xl p-5 border border-dark-700 text-center">
          <div class="text-3xl mb-1">⚡</div>
          <div class="text-2xl font-bold">{{ userStore.user.total_xp }}</div>
          <div class="text-sm text-gray-400">Total XP</div>
        </div>
        <div class="bg-dark-800 rounded-xl p-5 border border-dark-700 text-center">
          <StreakBadge :streak="userStore.user.current_streak || 0" class="text-xl" />
          <div class="text-sm text-gray-400 mt-1">Current Streak</div>
        </div>
      </div>

      <!-- Internship Timeline -->
      <div class="bg-dark-800 rounded-xl p-6 border border-dark-700">
        <h4 class="font-semibold mb-4">📅 Internship Timeline</h4>
        <div class="relative">
          <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-dark-600" />
          <div class="space-y-4 relative">
            <div class="flex gap-4">
              <div class="w-4 h-4 rounded-full bg-accent-emerald border-2 border-dark-800 -ml-0.5 mt-1" />
              <div>
                <p class="text-sm font-medium">Started</p>
                <p class="text-xs text-gray-500">{{ userStore.user.start_date ? new Date(userStore.user.start_date).toLocaleDateString('id-ID') : '-' }}</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="w-4 h-4 rounded-full bg-accent-gold border-2 border-dark-800 -ml-0.5 mt-1" />
              <div>
                <p class="text-sm font-medium">Ends</p>
                <p class="text-xs text-gray-500">{{ userStore.user.end_date ? new Date(userStore.user.end_date).toLocaleDateString('id-ID') : '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-gray-400 text-center py-12">Loading...</div>
  </div>
</template>
