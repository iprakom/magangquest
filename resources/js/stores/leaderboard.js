import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLeaderboardStore = defineStore('leaderboard', () => {
  const entries = ref([])
  const period = ref('all_time')
  const isLoading = ref(false)

  async function fetchLeaderboard() {
    isLoading.value = true
    try {
      const response = await fetch(`/api/leaderboard?period=${period.value}`)
      if (response.ok) {
        entries.value = await response.json()
      }
    } finally {
      isLoading.value = false
    }
  }

  return { entries, period, isLoading, fetchLeaderboard }
})
