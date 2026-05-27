<script setup>
import PriorityBadge from './PriorityBadge.vue'
import StatusBadge from './StatusBadge.vue'
defineProps({
  quest: { type: Object, required: true }
})
</script>
<template>
  <div class="bg-dark-800 border border-dark-700 rounded-xl p-5 hover:border-dark-600 transition">
    <div class="flex justify-between items-start mb-3">
      <h3 class="font-semibold text-white">{{ quest.title }}</h3>
      <PriorityBadge :priority="quest.priority" />
    </div>
    <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ quest.description }}</p>
    <div class="flex justify-between items-center text-sm">
      <span class="text-accent-gold font-bold">+{{ quest.xp_reward }} XP</span>
      <StatusBadge :status="quest.pivot?.status || quest.status" />
    </div>
    <div v-if="quest.pivot?.sla_deadline" class="mt-3 text-xs text-gray-500">
      Deadline: {{ new Date(quest.pivot.sla_deadline).toLocaleDateString('id-ID') }}
    </div>
  </div>
</template>
