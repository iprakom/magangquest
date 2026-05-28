<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Navigation Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo / Brand -->
          <div class="flex items-center gap-8">
            <h1 class="text-xl font-bold text-white">🎮 Magang Quest</h1>
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-1">
              <!-- Player Navigation -->
              <template v-if="userRole === 'player'">
                <a
                  href="/"
                  @click.prevent="$inertia.visit('/')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📊</span>
                  <span>Dashboard</span>
                </a>
                <a
                  href="/quest-logbook"
                  @click.prevent="$inertia.visit('/quest-logbook')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📋</span>
                  <span>Quest Logbook</span>
                </a>
                <a
                  href="/leaderboard"
                  @click.prevent="$inertia.visit('/leaderboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🏆</span>
                  <span>Leaderboard</span>
                </a>
                <a
                  href="/profile"
                  @click.prevent="$inertia.visit('/profile')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>👤</span>
                  <span>Profile</span>
                </a>
              </template>

              <!-- Mentor Navigation -->
              <template v-else-if="userRole === 'mentor'">
                <a
                  href="/"
                  @click.prevent="$inertia.visit('/')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📊</span>
                  <span>Dashboard</span>
                </a>
                <a
                  href="/quest-logbook"
                  @click.prevent="$inertia.visit('/quest-logbook')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📋</span>
                  <span>Quest Logbook</span>
                </a>
                <a
                  href="/leaderboard"
                  @click.prevent="$inertia.visit('/leaderboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🏆</span>
                  <span>Leaderboard</span>
                </a>
                <a
                  href="/profile"
                  @click.prevent="$inertia.visit('/profile')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>👤</span>
                  <span>Profile</span>
                </a>
                <a
                  href="/mentor/dashboard"
                  @click.prevent="$inertia.visit('/mentor/dashboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🎓</span>
                  <span>Mentor Dashboard</span>
                </a>
                <a
                  href="/mentor/quests/create"
                  @click.prevent="$inertia.visit('/mentor/quests/create')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/quests/create') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>➕</span>
                  <span>Create Quest</span>
                </a>
                <a
                  href="/mentor/review"
                  @click.prevent="$inertia.visit('/mentor/review')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/review') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🔍</span>
                  <span>Mentor Review</span>
                </a>
              </template>

              <!-- Admin Navigation -->
              <template v-else-if="userRole === 'admin'">
                <a
                  href="/"
                  @click.prevent="$inertia.visit('/')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📊</span>
                  <span>Dashboard</span>
                </a>
                <a
                  href="/quest-logbook"
                  @click.prevent="$inertia.visit('/quest-logbook')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📋</span>
                  <span>Quest Logbook</span>
                </a>
                <a
                  href="/leaderboard"
                  @click.prevent="$inertia.visit('/leaderboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/leaderboard') && !$page.url.startsWith('/admin/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🏆</span>
                  <span>Leaderboard</span>
                </a>
                <a
                  href="/profile"
                  @click.prevent="$inertia.visit('/profile')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>👤</span>
                  <span>Profile</span>
                </a>
                <a
                  href="/mentor/dashboard"
                  @click.prevent="$inertia.visit('/mentor/dashboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🎓</span>
                  <span>Mentor Dashboard</span>
                </a>
                <a
                  href="/mentor/quests/create"
                  @click.prevent="$inertia.visit('/mentor/quests/create')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/quests/create') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>➕</span>
                  <span>Create Quest</span>
                </a>
                <a
                  href="/mentor/review"
                  @click.prevent="$inertia.visit('/mentor/review')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/mentor/review') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🔍</span>
                  <span>Mentor Review</span>
                </a>
                <a
                  href="/admin/onboarding"
                  @click.prevent="$inertia.visit('/admin/onboarding')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/admin/onboarding') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>👥</span>
                  <span>Onboarding</span>
                </a>
                <a
                  href="/admin/holidays"
                  @click.prevent="$inertia.visit('/admin/holidays')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/admin/holidays') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>📅</span>
                  <span>Holidays</span>
                </a>
                <a
                  href="/admin/settings"
                  @click.prevent="$inertia.visit('/admin/settings')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/admin/settings') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>⚙️</span>
                  <span>Settings</span>
                </a>
                <a
                  href="/admin/leaderboard"
                  @click.prevent="$inertia.visit('/admin/leaderboard')"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="$page.url.startsWith('/admin/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
                >
                  <span>🏆</span>
                  <span>Admin Leaderboard</span>
                </a>
              </template>
            </nav>
          </div>

          <!-- Right Side - User Menu -->
          <div class="flex items-center gap-4">
            <!-- User Info -->
            <div class="hidden sm:flex items-center gap-3">
              <div class="text-right">
                <p class="text-white text-sm font-medium">{{ userName }}</p>
                <p class="text-slate-400 text-xs capitalize">{{ userRole }}</p>
              </div>
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                {{ userInitials }}
              </div>
            </div>

            <!-- Mobile Menu Button -->
            <button 
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors"
            >
              <span v-if="!mobileMenuOpen">☰</span>
              <span v-else>✕</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-700 bg-slate-800/95 backdrop-blur-sm">
        <div class="px-4 py-3 space-y-1">
          <!-- Player Navigation -->
          <template v-if="userRole === 'player'">
            <a
              href="/"
              @click.prevent="$inertia.visit('/'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📊</span>
              <span>Dashboard</span>
            </a>
            <a
              href="/quest-logbook"
              @click.prevent="$inertia.visit('/quest-logbook'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📋</span>
              <span>Quest Logbook</span>
            </a>
            <a
              href="/leaderboard"
              @click.prevent="$inertia.visit('/leaderboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🏆</span>
              <span>Leaderboard</span>
            </a>
            <a
              href="/profile"
              @click.prevent="$inertia.visit('/profile'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">👤</span>
              <span>Profile</span>
            </a>
          </template>

          <!-- Mentor Navigation -->
          <template v-else-if="userRole === 'mentor'">
            <a
              href="/"
              @click.prevent="$inertia.visit('/'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📊</span>
              <span>Dashboard</span>
            </a>
            <a
              href="/quest-logbook"
              @click.prevent="$inertia.visit('/quest-logbook'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📋</span>
              <span>Quest Logbook</span>
            </a>
            <a
              href="/leaderboard"
              @click.prevent="$inertia.visit('/leaderboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🏆</span>
              <span>Leaderboard</span>
            </a>
            <a
              href="/profile"
              @click.prevent="$inertia.visit('/profile'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">👤</span>
              <span>Profile</span>
            </a>
            <a
              href="/mentor/dashboard"
              @click.prevent="$inertia.visit('/mentor/dashboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🎓</span>
              <span>Mentor Dashboard</span>
            </a>
            <a
              href="/mentor/quests/create"
              @click.prevent="$inertia.visit('/mentor/quests/create'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/quests/create') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">➕</span>
              <span>Create Quest</span>
            </a>
            <a
              href="/mentor/review"
              @click.prevent="$inertia.visit('/mentor/review'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/review') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🔍</span>
              <span>Mentor Review</span>
            </a>
          </template>

          <!-- Admin Navigation -->
          <template v-else-if="userRole === 'admin'">
            <a
              href="/"
              @click.prevent="$inertia.visit('/'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url === '/' ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📊</span>
              <span>Dashboard</span>
            </a>
            <a
              href="/quest-logbook"
              @click.prevent="$inertia.visit('/quest-logbook'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/quest-logbook') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📋</span>
              <span>Quest Logbook</span>
            </a>
            <a
              href="/leaderboard"
              @click.prevent="$inertia.visit('/leaderboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/leaderboard') && !$page.url.startsWith('/admin/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🏆</span>
              <span>Leaderboard</span>
            </a>
            <a
              href="/profile"
              @click.prevent="$inertia.visit('/profile'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/profile') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">👤</span>
              <span>Profile</span>
            </a>
            <a
              href="/mentor/dashboard"
              @click.prevent="$inertia.visit('/mentor/dashboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🎓</span>
              <span>Mentor Dashboard</span>
            </a>
            <a
              href="/mentor/quests/create"
              @click.prevent="$inertia.visit('/mentor/quests/create'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/quests/create') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">➕</span>
              <span>Create Quest</span>
            </a>
            <a
              href="/mentor/review"
              @click.prevent="$inertia.visit('/mentor/review'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/mentor/review') ? 'bg-purple-600/20 text-purple-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🔍</span>
              <span>Mentor Review</span>
            </a>
            <a
              href="/admin/onboarding"
              @click.prevent="$inertia.visit('/admin/onboarding'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/admin/onboarding') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">👥</span>
              <span>Onboarding</span>
            </a>
            <a
              href="/admin/holidays"
              @click.prevent="$inertia.visit('/admin/holidays'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/admin/holidays') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">📅</span>
              <span>Holidays</span>
            </a>
            <a
              href="/admin/settings"
              @click.prevent="$inertia.visit('/admin/settings'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/admin/settings') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">⚙️</span>
              <span>Settings</span>
            </a>
            <a
              href="/admin/leaderboard"
              @click.prevent="$inertia.visit('/admin/leaderboard'); mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-colors"
              :class="$page.url.startsWith('/admin/leaderboard') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
            >
              <span class="text-lg">🏆</span>
              <span>Admin Leaderboard</span>
            </a>
          </template>

          <!-- Mobile User Section -->
          <div class="pt-3 mt-3 border-t border-slate-700">
            <div class="flex items-center gap-3 px-3 py-2">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                {{ userInitials }}
              </div>
              <div>
                <p class="text-white text-sm font-medium">{{ userName }}</p>
                <p class="text-slate-400 text-xs capitalize">{{ userRole }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const mobileMenuOpen = ref(false)

// Get user data from Inertia shared data
const user = computed(() => page.props.auth?.user)
const userName = computed(() => user.value?.name || 'Guest')
const userRole = computed(() => user.value?.role || 'player')

// Calculate user initials
const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
})
</script>
