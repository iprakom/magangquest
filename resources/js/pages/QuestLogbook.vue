<script setup>
import { ref, onMounted } from 'vue'
import { useQuestsStore } from '../stores/quests'
import QuestCard from '../components/QuestCard.vue'

const questsStore = useQuestsStore()
const tabs = [
  { key: 'assigned', label: '📋 Assigned' },
  { key: 'bounty', label: '💰 Bounty' },
  { key: 'usulan', label: '📝 Usulan' },
]
onMounted(() => questsStore.fetchQuests())

async function handleStart(quest) {
  await questsStore.startQuest(quest.id)
}
async function handleSubmit(quest) {
  await questsStore.submitQuest(quest.id)
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold">Quest Logbook</h2>
      <div class="text-sm text-gray-400">
        WIP: {{ questsStore.wipUsed }}/{{ questsStore.wipTotal }} slots used
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-dark-800 p-1 rounded-lg w-fit">
      <button
        v-for="tab in tabs" :key="tab.key"
        @click="questsStore.activeTab = tab.key"
        class="px-4 py-2 rounded-md text-sm font-medium transition"
        :class="questsStore.activeTab === tab.key ? 'bg-accent-emerald text-white' : 'text-gray-400 hover:text-white'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Quest Grid -->
    <div v-if="questsStore.isLoading" class="text-gray-400">Loading...</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="quest in questsStore[questsStore.activeTab === 'assigned' ? 'assignedQuests' : questsStore.activeTab === 'bounty' ? 'bountyQuests' : 'usulanQuests']" :key="quest.id">
        <QuestCard :quest="quest" />
        <div class="mt-2 flex gap-2">
          <button v-if="quest.pivot?.status === 'pending' && questsStore.wipUsed < questsStore.wipTotal"
            @click="handleStart(quest)"
            class="flex-1 bg-accent-emerald hover:bg-emerald-600 text-white text-sm py-2 rounded-lg transition">
            Start
          </button>
          <button v-if="quest.pivot?.status === 'in_progress'"
            @click="handleSubmit(quest)"
            class="flex-1 bg-accent-gold hover:bg-yellow-600 text-white text-sm py-2 rounded-lg transition">
            Submit
          </button>
        </div>
      </div>
      <div v-if="questsStore[questsStore.activeTab === 'assigned' ? 'assignedQuests' : questsStore.activeTab === 'bounty' ? 'bountyQuests' : 'usulanQuests'].length === 0"
        class="col-span-full text-center py-12 text-gray-500">
        No quests in this category
      </div>
    </div>
  </div>
</template>
