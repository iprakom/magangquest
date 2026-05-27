import { defineStore } from 'pinia'

export const useLeaderboardStore = defineStore('leaderboard', {
  state: () => ({
    rankings: [],
    myRank: null,
    timeFilter: 'all', // weekly, monthly, all
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),

  getters: {
    topPlayers: (state) => state.rankings.slice(0, 10),
    isEmpty: (state) => state.rankings.length === 0,
  },

  actions: {
    async fetchLeaderboard(filter = 'all') {
      this.loading = true
      this.error = null
      this.timeFilter = filter

      try {
        const response = await axios.get('/api/leaderboard', {
          params: { filter }
        })
        this.rankings = response.data.rankings
        this.myRank = response.data.my_rank
        this.pagination = response.data.pagination
        this.loading = false
      } catch (error) {
        // If endpoint doesn't exist yet, use mock data for demo
        if (error.response?.status === 404) {
          this.rankings = this.generateMockRankings()
          this.myRank = this.generateMockMyRank()
        } else {
          this.error = error.response?.data?.message || 'Failed to fetch leaderboard'
        }
        this.loading = false
      }
    },

    generateMockRankings() {
      const names = [
        'Budi Santoso', 'Ani Wijaya', 'Dewi Kusuma', 'Eko Prasetyo', 'Fitri Handayani',
        'Galang Ramadhan', 'Hana Sukma', 'Irfan Hakim', 'Jasmine Putri', 'Kevin Wijaya'
      ]
      return names.map((name, index) => ({
        rank: index + 1,
        user_id: index + 1,
        name,
        avatar: null,
        xp: Math.max(100, 1000 - (index * 80)),
        level: Math.floor((1000 - (index * 80)) / 100) + 1,
        department: 'Pusdiklat BPS',
        is_current_user: index === 5, // Mock current user at rank 6
      }))
    },

    generateMockMyRank() {
      return {
        rank: 6,
        user_id: 99,
        name: 'Anda',
        xp: 520,
        level: 6,
        department: 'Pusdiklat BPS',
      }
    },

    setTimeFilter(filter) {
      this.timeFilter = filter
      this.fetchLeaderboard(filter)
    },
  },
})