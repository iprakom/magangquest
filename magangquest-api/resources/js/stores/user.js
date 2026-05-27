import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    profile: null,
    stats: null,
    streak: null,
    perfectDays: 0,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    isPlayer: (state) => state.user?.role === 'player',
    isMentor: (state) => state.user?.role === 'mentor',
    isAdmin: (state) => state.user?.role === 'admin',
    currentXP: (state) => state.stats?.current_xp ?? 0,
    level: (state) => state.stats?.level ?? 1,
    nextLevelXP: (state) => {
      const level = state.stats?.level ?? 1
      return level * 100 // Simple formula: level * 100 XP per level
    },
    xpProgress: (state) => {
      const current = state.stats?.current_xp ?? 0
      const level = state.stats?.level ?? 1
      const threshold = level * 100
      const previousThreshold = (level - 1) * 100
      return ((current - previousThreshold) / (threshold - previousThreshold)) * 100
    },
    daysRemaining: (state) => {
      if (!state.user?.end_date) return null
      const end = new Date(state.user.end_date)
      const now = new Date()
      return Math.max(0, Math.ceil((end - now) / (1000 * 60 * 60 * 24)))
    },
    isInCriticalZone: (state) => {
      const days = state.user?.end_date
        ? Math.ceil((new Date(state.user.end_date) - new Date()) / (1000 * 60 * 60 * 24))
        : null
      return days !== null && days <= 10 && days >= 0
    },
  },

  actions: {
    async fetchUser() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/user')
        this.user = response.data.user
        this.loading = false
        return this.user
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch user'
        this.loading = false
        throw error
      }
    },

    async fetchProfileStats() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/profile/stats')
        this.stats = response.data.stats
        this.streak = response.data.streak
        this.perfectDays = response.data.perfect_days ?? 0
        this.loading = false
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch profile stats'
        this.loading = false
        throw error
      }
    },

    async fetchPointTransactions(limit = 10) {
      try {
        const response = await axios.get('/api/point-transactions', {
          params: { limit }
        })
        return response.data.transactions
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch transactions'
        throw error
      }
    },

    clearUser() {
      this.user = null
      this.profile = null
      this.stats = null
      this.streak = null
    },
  },
})