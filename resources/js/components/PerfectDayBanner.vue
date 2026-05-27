<script setup>
defineProps({ show: { type: Boolean, default: false } })
</script>
<template>
  <div v-if="show" class="bg-accent-gold/20 border border-accent-gold text-accent-gold px-4 py-3 rounded-lg text-center font-bold animate-pulse">
    🎉 +15 Perfect Day Bonus! 3+ quests completed today!
  </div>
</template>
