import { defineStore } from 'pinia'
import { router } from '@inertiajs/vue3'

export const useQuestStore = defineStore('quest', {
  state: () => ({
    quests: [],
    currentQuest: null,
    assignments: [],
    currentAssignment: null,
    wipSlots: {
      used: 0,
      max: 0,
      available: 0,
    },
    filters: {
      type: '',
      difficulty: '',
      is_active: true,
    },
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    },
    loading: false,
    error: null,
  }),

  getters: {
    getQuestById: (state) => (id) => {
      return state.quests.find(quest => quest.id === id)
    },

    availableQuests: (state) => {
      return state.quests.filter(quest => quest.is_active)
    },

    bountyQuests: (state) => {
      return state.quests.filter(quest => quest.type === 'bounty' && quest.is_active)
    },

    myAssignments: (state) => {
      return state.assignments
    },
  },

  actions: {
    // Fetch quests with filters
    async fetchQuests(filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = { ...this.filters, ...filters }
        const response = await axios.get('/api/quests', { params })

        this.quests = response.data.quests
        this.pagination = response.data.pagination
        this.loading = false
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch quests'
        this.loading = false
        throw error
      }
    },

    // Fetch single quest
    async fetchQuest(id) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get(`/api/quests/${id}`)
        this.currentQuest = response.data.quest
        this.loading = false
        return this.currentQuest
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch quest'
        this.loading = false
        throw error
      }
    },

    // Create new quest (Admin only)
    async createQuest(questData) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/api/admin/quests', questData)
        const newQuest = response.data.quest
        this.quests.unshift(newQuest)
        this.loading = false
        return newQuest
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create quest'
        this.loading = false
        throw error
      }
    },

    // Update quest (Admin only)
    async updateQuest(id, questData) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.put(`/api/admin/quests/${id}`, questData)
        const updatedQuest = response.data.quest
        const index = this.quests.findIndex(q => q.id === id)
        if (index !== -1) {
          this.quests[index] = updatedQuest
        }
        if (this.currentQuest?.id === id) {
          this.currentQuest = updatedQuest
        }
        this.loading = false
        return updatedQuest
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update quest'
        this.loading = false
        throw error
      }
    },

    // Delete quest (Admin only)
    async deleteQuest(id) {
      this.loading = true
      this.error = null

      try {
        await axios.delete(`/api/admin/quests/${id}`)
        this.quests = this.quests.filter(q => q.id !== id)
        if (this.currentQuest?.id === id) {
          this.currentQuest = null
        }
        this.loading = false
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete quest'
        this.loading = false
        throw error
      }
    },

    // Fetch my assignments
    async fetchAssignments(filters = {}) {
      this.loading = true
      this.error = null

      try {
        const params = filters
        const response = await axios.get('/api/quest-assignments/my', { params })

        this.assignments = response.data.assignments
        this.wipSlots = response.data.wip_slots
        this.pagination = response.data.pagination
        this.loading = false
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch assignments'
        this.loading = false
        throw error
      }
    },

    // Accept/claim a quest
    async acceptQuest(questId) {
      this.loading = true
      this.error = null

      try {
        // Use user-facing endpoint for bounty claims
        const response = await axios.post(`/api/quests/${questId}/claim`)
        this.assignments.unshift(response.data.assignment)
        this.wipSlots = response.data.wip_slots
        this.loading = false
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to accept quest'
        this.loading = false
        throw error
      }
    },

    // Update assignment status
    async updateAssignmentStatus(assignmentId, status, mentorNotes = null) {
      this.loading = true
      this.error = null

      try {
        const data = { status }
        if (mentorNotes) {
          data.mentor_notes = mentorNotes
        }

        const response = await axios.put(
          `/api/admin/quest-assignments/${assignmentId}/status`,
          data
        )

        const index = this.assignments.findIndex(a => a.id === assignmentId)
        if (index !== -1) {
          this.assignments[index] = response.data.assignment
        }
        this.loading = false
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update assignment status'
        this.loading = false
        throw error
      }
    },

    // Fetch WIP slots
    async fetchWipSlots() {
      try {
        const response = await axios.get('/api/quest-assignments/wip-slots')
        this.wipSlots = response.data.wip_slots
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch WIP slots'
        throw error
      }
    },

    // Add progress entry to an assignment
    async addProgress(assignmentId, notes, evidenceFile = null) {
      this.loading = true
      this.error = null

      try {
        const formData = new FormData()
        formData.append('notes', notes)
        if (evidenceFile) {
          formData.append('evidence', evidenceFile)
        }

        const response = await axios.post(
          `/api/quest-assignments/${assignmentId}/progress`,
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          }
        )

        this.loading = false
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to add progress'
        this.loading = false
        throw error
      }
    },

    // Submit assignment for review
    async submitForReview(assignmentId) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post(
          `/api/quest-assignments/${assignmentId}/submit-review`
        )

        // Update the assignment in the local state
        const index = this.assignments.findIndex(a => a.id === assignmentId)
        if (index !== -1) {
          this.assignments[index] = response.data.assignment
        }

        this.loading = false
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to submit for review'
        this.loading = false
        throw error
      }
    },

    // Update filters
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    // Clear filters
    clearFilters() {
      this.filters = {
        type: '',
        difficulty: '',
        is_active: true,
      }
    },

    // Clear error
    clearError() {
      this.error = null
    },
  },
})
