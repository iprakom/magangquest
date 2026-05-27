import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useQuestsStore = defineStore('quests', () => {
  const quests = ref([])
  const currentQuest = ref(null)
  const isLoading = ref(false)
  const activeTab = ref('assigned')

  const assignedQuests = computed(() => quests.value.filter(q => q.access_path === 'assigned'))
  const bountyQuests = computed(() => quests.value.filter(q => q.is_bounty))
  const usulanQuests = computed(() => quests.value.filter(q => q.access_path === 'usulan'))
  const wipUsed = computed(() => quests.value.filter(q => q.status === 'in_progress').length)
  const wipTotal = computed(() => 4) // Global_Limit * 4

  async function fetchQuests() {
    isLoading.value = true
    try {
      const response = await fetch('/api/quests')
      if (response.ok) {
        quests.value = await response.json()
      }
    } finally {
      isLoading.value = false
    }
  }

  async function startQuest(questId) {
    const response = await fetch(`/api/quests/${questId}/start`, { method: 'POST' })
    if (response.ok) {
      await fetchQuests()
    }
    return response.ok
  }

  async function submitQuest(questId) {
    const response = await fetch(`/api/quests/${questId}/submit`, { method: 'POST' })
    if (response.ok) {
      await fetchQuests()
    }
    return response.ok
  }

  return { quests, currentQuest, isLoading, activeTab, assignedQuests, bountyQuests, usulanQuests, wipUsed, wipTotal, fetchQuests, startQuest, submitQuest }
})
