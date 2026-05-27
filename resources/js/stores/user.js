import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUserStore = defineStore('user', () => {
  const user = ref(null)
  const isLoading = ref(false)

  const isPlayer = computed(() => user.value?.role?.name === 'player')
  const isMentor = computed(() => user.value?.role?.name === 'mentor')
  const isAdmin = computed(() => user.value?.role?.name === 'super_admin')
  const level = computed(() => Math.floor((user.value?.total_xp || 0) / 1000) + 1)
  const xpToNextLevel = computed(() => (level.value * 1000) - (user.value?.total_xp || 0))

  async function fetchUser() {
    isLoading.value = true
    try {
      const response = await fetch('/api/user')
      if (response.ok) {
        user.value = await response.json()
      }
    } finally {
      isLoading.value = false
    }
  }

  return { user, isLoading, isPlayer, isMentor, isAdmin, level, xpToNextLevel, fetchUser }
})
