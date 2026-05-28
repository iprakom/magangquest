<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur-sm border-b border-slate-700">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-white">Buat Quest Baru</h1>
            <p class="text-slate-400 text-sm">Buat quest bounty atau tugas untuk intern</p>
          </div>
          <Link
            href="/mentor/dashboard"
            class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors flex items-center gap-2"
          >
            ← Kembali
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Form Card -->
      <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
        <form @submit.prevent="submitForm">
          <!-- Title -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-2">
              Judul Quest <span class="text-red-400">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              maxlength="255"
              placeholder="Contoh: Membuat Laporan Mingguan"
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 placeholder-slate-400"
            />
          </div>

          <!-- Description -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-2">
              Deskripsi
            </label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Deskripsi detail tentang quest..."
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 placeholder-slate-400"
            ></textarea>
          </div>

          <!-- Type Selection -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-3">
              Tipe Quest <span class="text-red-400">*</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Bounty -->
              <label
                class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all"
                :class="form.type === 'bounty' ? 'border-blue-500 bg-blue-500/10' : 'border-slate-600 bg-slate-700/50 hover:border-slate-500'"
              >
                <input
                  v-model="form.type"
                  type="radio"
                  value="bounty"
                  class="sr-only"
                />
                <div class="flex flex-col">
                  <span class="text-lg font-medium" :class="form.type === 'bounty' ? 'text-blue-400' : 'text-white'">
                    🎯 Bounty
                  </span>
                  <span class="text-sm mt-1" :class="form.type === 'bounty' ? 'text-blue-300' : 'text-slate-400'">
                    Terbuka untuk semua intern yang eligible
                  </span>
                </div>
              </label>

              <!-- Assigned -->
              <label
                class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all"
                :class="form.type === 'assigned' ? 'border-purple-500 bg-purple-500/10' : 'border-slate-600 bg-slate-700/50 hover:border-slate-500'"
              >
                <input
                  v-model="form.type"
                  type="radio"
                  value="assigned"
                  class="sr-only"
                />
                <div class="flex flex-col">
                  <span class="text-lg font-medium" :class="form.type === 'assigned' ? 'text-purple-400' : 'text-white'">
                    👤 Assigned
                  </span>
                  <span class="text-sm mt-1" :class="form.type === 'assigned' ? 'text-purple-300' : 'text-slate-400'">
                    Ditugaskan ke intern tertentu
                  </span>
                </div>
              </label>
            </div>
          </div>

          <!-- Intern Selection (for Assigned type) -->
          <div v-if="form.type === 'assigned'" class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-2">
              Pilih Intern <span class="text-red-400">*</span>
            </label>
            <select
              v-model="form.user_id"
              required
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-purple-500 focus:ring-purple-500"
            >
              <option value="">-- Pilih Intern --</option>
              <option v-for="intern in interns" :key="intern.id" :value="intern.id">
                {{ intern.name }} ({{ intern.room }}) - {{ intern.slots.available }} slots available
              </option>
            </select>

            <!-- Slot Impact Warning -->
            <div v-if="selectedIntern" class="mt-3 p-3 rounded-lg" :class="selectedIntern.slots.available >= slotImpact ? 'bg-green-500/10 border border-green-500/30' : 'bg-red-500/10 border border-red-500/30'">
              <p class="text-sm" :class="selectedIntern.slots.available >= slotImpact ? 'text-green-400' : 'text-red-400'">
                <strong>Impact Slot:</strong> Quest ini akan menggunakan <strong>{{ slotImpact }} slot</strong> dari intern.
                <br />Intern memiliki <strong>{{ selectedIntern.slots.available }} slot</strong> tersedia.
              </p>
            </div>
          </div>

          <!-- Priority -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-3">
              Priority <span class="text-red-400">*</span>
            </label>
            <div class="grid grid-cols-3 gap-3">
              <label
                v-for="p in priorities"
                :key="p.value"
                class="relative flex cursor-pointer rounded-lg border p-3 focus:outline-none transition-all text-center"
                :class="form.priority === p.value ? `border-${p.color}-500 bg-${p.color}-500/10` : 'border-slate-600 bg-slate-700/50 hover:border-slate-500'"
              >
                <input
                  v-model="form.priority"
                  type="radio"
                  :value="p.value"
                  class="sr-only"
                />
                <div class="flex flex-col items-center mx-auto">
                  <span class="text-xl mb-1">{{ p.icon }}</span>
                  <span class="text-sm font-medium" :class="form.priority === p.value ? `text-${p.color}-400` : 'text-white'">
                    {{ p.label }}
                  </span>
                  <span class="text-xs mt-1" :class="form.priority === p.value ? `text-${p.color}-300` : 'text-slate-400'">
                    {{ p.slots }} slots
                  </span>
                </div>
              </label>
            </div>
          </div>

          <!-- Due Date -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-300 mb-2">
              Batas Waktu (Due Date)
            </label>
            <input
              v-model="form.due_date"
              type="date"
              :min="minDate"
              class="w-full rounded-md border-slate-600 bg-slate-700 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
            <p class="text-xs text-slate-400 mt-1">Kosongkan jika tidak ada batas waktu tertentu</p>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg">
            <p class="text-red-400">{{ error }}</p>
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
            <p class="text-green-400">{{ successMessage }}</p>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end gap-4">
            <Link
              href="/mentor/dashboard"
              class="px-6 py-3 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors"
            >
              Batal
            </Link>
            <button
              type="submit"
              :disabled="loading || (form.type === 'assigned' && !form.user_id)"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="loading" class="animate-spin">⏳</span>
              {{ loading ? 'Menyimpan...' : 'Buat Quest' }}
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const interns = ref([])

const form = ref({
  title: '',
  description: '',
  type: 'bounty',
  priority: 'mid',
  user_id: '',
  due_date: '',
})

const priorities = [
  { value: 'high', label: 'High', icon: '🔴', color: 'red', slots: 4 },
  { value: 'mid', label: 'Mid', icon: '🟡', color: 'yellow', slots: 2 },
  { value: 'low', label: 'Low', icon: '🟢', color: 'green', slots: 1 },
]

const slotWeights = {
  high: 4,
  mid: 2,
  low: 1,
}

const slotImpact = computed(() => slotWeights[form.value.priority])

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const selectedIntern = computed(() => {
  if (!form.value.user_id) return null
  return interns.value.find(i => i.id === form.value.user_id)
})

async function fetchInterns() {
  try {
    const response = await axios.get('/api/mentor/interns')
    interns.value = response.data.interns
  } catch (err) {
    console.error('Failed to fetch interns:', err)
    error.value = 'Gagal memuat daftar intern'
  }
}

async function submitForm() {
  loading.value = true
  error.value = null
  successMessage.value = null

  try {
    // Create the quest first
    const questResponse = await axios.post('/api/mentor/quests', {
      title: form.value.title,
      description: form.value.description,
      type: form.value.type,
      priority: form.value.priority,
      due_date: form.value.due_date || null,
    })

    const quest = questResponse.data.quest

    // If assigned type, also assign to the intern
    if (form.value.type === 'assigned' && form.value.user_id) {
      await axios.post('/api/mentor/assign', {
        quest_id: quest.id,
        user_id: form.value.user_id,
      })
    }

    successMessage.value = 'Quest berhasil dibuat!'
    
    // Reset form
    form.value = {
      title: '',
      description: '',
      type: 'bounty',
      priority: 'mid',
      user_id: '',
      due_date: '',
    }

    // Redirect after delay
    setTimeout(() => {
      router.visit('/mentor/dashboard')
    }, 1500)
  } catch (err) {
    console.error('Failed to create quest:', err)
    error.value = err.response?.data?.message || 'Gagal membuat quest. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInterns()
})
</script>
