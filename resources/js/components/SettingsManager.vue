<script setup>
import { ref, onMounted } from 'vue'

const settings = ref({})
const isLoading = ref(false)
const error = ref('')
const success = ref('')

async function fetchSettings() {
  isLoading.value = true
  try {
    const response = await fetch('/api/admin/settings')
    if (response.ok) {
      const data = await response.json()
      // Convert array to object keyed by key
      settings.value = data.settings.reduce((acc, s) => {
        acc[s.key] = s
        return acc
      }, {})
    }
  } catch (e) {
    error.value = 'Failed to fetch settings'
  } finally {
    isLoading.value = false
  }
}

async function updateSetting(key) {
  error.value = ''
  success.value = ''
  
  const setting = settings.value[key]
  if (!setting) return

  isLoading.value = true
  
  try {
    const response = await fetch(`/api/admin/settings/${key}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ value: setting.value })
    })

    if (response.ok) {
      success.value = `${setting.description || key} updated successfully`
      await fetchSettings()
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to update setting'
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    isLoading.value = false
  }
}

function getSettingInputType(type) {
  switch (type) {
    case 'integer': return 'number'
    case 'boolean': return 'checkbox'
    default: return 'text'
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<template>
  <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
    <h3 class="text-xl font-bold mb-6">System Settings</h3>

    <div v-if="error" class="bg-red-900/30 border border-red-700 rounded-lg p-3 mb-4 text-red-400 text-sm">
      {{ error }}
    </div>
    
    <div v-if="success" class="bg-green-900/30 border border-green-700 rounded-lg p-3 mb-4 text-green-400 text-sm">
      {{ success }}
    </div>

    <div v-if="isLoading && Object.keys(settings).length === 0" class="text-center py-8 text-gray-500">
      Loading settings...
    </div>

    <div v-else class="space-y-6">
      <!-- Global Limit Setting -->
      <div v-if="settings.global_limit" class="bg-dark-700 rounded-lg p-4 border border-dark-600">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h4 class="font-semibold">{{ settings.global_limit.description || 'Global WIP Limit' }}</h4>
            <p class="text-sm text-gray-400 mt-1">
              Max WIP per intern = Global_Limit × 4 (default: 4 × 4 = 16 slots)
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <input type="number" v-model="settings.global_limit.value" min="1" max="10"
            class="w-24 bg-dark-800 border border-dark-600 rounded-lg px-3 py-2 text-white
              focus:border-accent-gold focus:outline-none" />
          <span class="text-gray-400 text-sm">× 4 = {{ (parseInt(settings.global_limit.value) || 0) * 4 }} max slots</span>
          <button @click="updateSetting('global_limit')" :disabled="isLoading"
            class="ml-auto bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            Save
          </button>
        </div>
      </div>

      <!-- Other Settings -->
      <div v-for="(setting, key) in settings" :key="key"
        v-if="key !== 'global_limit' && key !== 'slot_multiplier'"
        class="bg-dark-700 rounded-lg p-4 border border-dark-600">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h4 class="font-semibold">{{ setting.description || key }}</h4>
            <p class="text-xs text-gray-500 mt-1">Key: {{ key }} | Type: {{ setting.type }}</p>
          </div>
        </div>
        
        <div v-if="setting.type === 'integer'" class="flex items-center gap-4">
          <input type="number" v-model="setting.value" 
            class="w-32 bg-dark-800 border border-dark-600 rounded-lg px-3 py-2 text-white
              focus:border-accent-gold focus:outline-none" />
          <button @click="updateSetting(key)" :disabled="isLoading"
            class="ml-auto bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            Save
          </button>
        </div>

        <div v-else-if="setting.type === 'boolean'" class="flex items-center gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="setting.value"
              class="w-5 h-5 rounded border-dark-600 bg-dark-800 text-accent-gold focus:ring-accent-gold" />
            <span class="text-gray-400">{{ setting.value ? 'Enabled' : 'Disabled' }}</span>
          </label>
          <button @click="updateSetting(key)" :disabled="isLoading"
            class="ml-auto bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            Save
          </button>
        </div>

        <div v-else class="flex items-center gap-4">
          <input type="text" v-model="setting.value"
            class="flex-1 bg-dark-800 border border-dark-600 rounded-lg px-3 py-2 text-white
              focus:border-accent-gold focus:outline-none" />
          <button @click="updateSetting(key)" :disabled="isLoading"
            class="bg-accent-gold hover:bg-accent-gold/80 disabled:bg-dark-600
              text-dark-900 font-semibold py-2 px-4 rounded-lg transition-colors">
            Save
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
