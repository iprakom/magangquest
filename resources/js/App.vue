<script setup>
import { onMounted } from 'vue'
import { useUserStore } from './stores/user'
import AppLayout from './layouts/AppLayout.vue'

const userStore = useUserStore()
onMounted(() => userStore.fetchUser())
</script>

<template>
  <AppLayout>
    <slot />
  </AppLayout>
</template>
