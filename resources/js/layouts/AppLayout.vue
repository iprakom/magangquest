<script setup>
import { ref } from 'vue'
import { useUserStore } from '../stores/user'
import XpBar from '../components/XpBar.vue'
import StreakBadge from '../components/StreakBadge.vue'

const userStore = useUserStore()
const sidebarOpen = ref(false)
const navItems = [
  { name: 'Dashboard', href: '/dashboard', icon: '🏠' },
  { name: 'Quest Logbook', href: '/quests', icon: '📜' },
  { name: 'Leaderboard', href: '/leaderboard', icon: '🏆' },
  { name: 'Profile', href: '/profile', icon: '👤' },
]
</script>

<template>
  <div class="min-h-screen bg-dark-900 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-dark-800 border-r border-dark-700 flex flex-col">
      <div class="p-6 border-b border-dark-700">
        <h1 class="text-xl font-bold text-accent-gold">⚔️ Magang Quest</h1>
      </div>
      <nav class="flex-1 p-4 space-y-1">
        <a
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-dark-700 hover:text-white transition"
        >
          <span>{{ item.icon }}</span>
          <span>{{ item.name }}</span>
        </a>
      </nav>
      <div v-if="userStore.user" class="p-4 border-t border-dark-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-accent-emerald flex items-center justify-center font-bold">
            {{ userStore.user.name?.charAt(0) || 'P' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ userStore.user.name }}</p>
            <p class="text-xs text-gray-500">{{ userStore.user.role?.display_name }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="bg-dark-800 border-b border-dark-700 px-6 py-4 flex items-center gap-6">
        <div class="flex-1">
          <XpBar v-if="userStore.user" :xp="userStore.user.total_xp" :level="userStore.level" />
        </div>
        <StreakBadge v-if="userStore.user" :streak="userStore.user.current_streak || 0" />
      </header>
      <!-- Page content -->
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
