<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">⚙️ Pengaturan Sistem</h1>
                <p class="text-slate-400 text-sm mt-1">Konfigurasi parameter global untuk semua intern</p>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <p class="text-slate-400">Memuat data...</p>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-6 p-4 bg-red-900/50 border border-red-500/50 rounded-lg">
                <p class="text-red-300 text-sm">{{ error }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="mb-6 p-4 bg-green-900/50 border border-green-500/50 rounded-lg">
                <p class="text-green-300 text-sm">{{ successMessage }}</p>
            </div>

            <div v-if="!loading">
                <!-- Global Limit Card -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-white mb-1">🎯 Global Limit (WIP Limit)</h2>
                            <p class="text-slate-400 text-sm">Batas maksimum slot kerja untuk semua intern</p>
                        </div>
                        <div class="bg-blue-900/50 px-3 py-1 rounded-lg">
                            <span class="text-blue-300 text-sm font-medium">Admin Only</span>
                        </div>
                    </div>

                    <!-- Current Value Display -->
                    <div class="bg-slate-700/50 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-300 text-sm">Nilai Saat Ini:</span>
                            <span class="text-3xl font-bold text-white">{{ globalLimit }}</span>
                        </div>
                    </div>

                    <!-- Impact Info -->
                    <div class="bg-green-900/30 border border-green-700/50 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📊</span>
                            <div>
                                <p class="text-green-300 font-medium">Dampak Perubahan</p>
                                <p class="text-green-200/70 text-sm">
                                    Global Limit <strong>{{ globalLimit }}</strong> → Max Slots = <strong>{{ globalLimit }} × 4 = {{ globalLimit * 4 }}</strong> slot
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="border-t border-slate-700 pt-4">
                        <h3 class="text-white font-medium mb-3">Ubah Nilai</h3>
                        <div class="flex items-end gap-4">
                            <div class="flex-1">
                                <label class="block text-slate-300 text-sm font-medium mb-1">Global Limit (1-10)</label>
                                <input
                                    v-model.number="newGlobalLimit"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-blue-500 focus:outline-none"
                                />
                                <p v-if="newGlobalLimit < 1 || newGlobalLimit > 10" class="text-red-400 text-xs mt-1">
                                    Nilai harus antara 1 dan 10
                                </p>
                            </div>
                            <button
                                @click="saveGlobalLimit"
                                :disabled="saving || !isValidNewValue"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ saving ? 'Menyimpan...' : '💾 Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Other Settings (Read-only display) -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4">📋 Pengaturan Lainnya</h2>
                    <div class="space-y-3">
                        <div
                            v-for="setting in otherSettings"
                            :key="setting.key"
                            class="flex items-center justify-between py-2 border-b border-slate-700 last:border-0"
                        >
                            <div>
                                <p class="text-white text-sm font-medium">{{ setting.description || setting.key }}</p>
                                <p class="text-slate-500 text-xs">{{ setting.key }}</p>
                            </div>
                            <span class="text-slate-300 font-mono">{{ setting.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Audit Trail -->
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-6">
                    <h2 class="text-xl font-bold text-white mb-4">📜 Riwayat Perubahan</h2>
                    <div v-if="auditLogs.length === 0" class="text-center py-6 text-slate-400 text-sm">
                        Belum ada riwayat perubahan pengaturan
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="(log, index) in auditLogs"
                            :key="index"
                            class="flex items-center justify-between py-2 border-b border-slate-700 last:border-0"
                        >
                            <div>
                                <p class="text-white text-sm">
                                    <span class="text-slate-400">{{ log.user_name }}</span>
                                    mengubah <strong>{{ log.key }}</strong>
                                </p>
                                <p class="text-slate-500 text-xs">{{ formatDateTime(log.created_at) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 text-sm">{{ log.old_value }}</span>
                                <span class="text-slate-500 mx-2">→</span>
                                <span class="text-green-400 font-medium">{{ log.new_value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const successMessage = ref(null)
const globalLimit = ref(4)
const newGlobalLimit = ref(4)
const allSettings = ref([])
const auditLogs = ref([])

const isValidNewValue = computed(() => {
    return newGlobalLimit.value >= 1 && newGlobalLimit.value <= 10
})

const otherSettings = computed(() => {
    return allSettings.value.filter(s => s.key !== 'global_limit')
})

onMounted(() => {
    fetchSettings()
    fetchAuditLogs()
})

function fetchSettings() {
    loading.value = true

    router.get('/api/admin/settings', {}, {
        preserveScroll: true,
        onSuccess: (data) => {
            allSettings.value = data.settings || []
            const gl = allSettings.value.find(s => s.key === 'global_limit')
            if (gl) {
                globalLimit.value = parseInt(gl.value) || 4
                newGlobalLimit.value = globalLimit.value
            }
            loading.value = false
        },
        onError: (err) => {
            error.value = 'Gagal memuat pengaturan'
            loading.value = false
            console.error(err)
        }
    })
}

function fetchAuditLogs() {
    router.get('/api/admin/settings/audit', {}, {
        preserveScroll: true,
        onSuccess: (data) => {
            auditLogs.value = data.audit_logs || []
        },
        onError: (err) => {
            console.error('Failed to fetch audit logs:', err)
        }
    })
}

function saveGlobalLimit() {
    if (!isValidNewValue.value) return

    saving.value = true
    error.value = null
    successMessage.value = null

    router.put(`/api/admin/settings/global_limit`, { value: newGlobalLimit.value }, {
        preserveScroll: true,
        onSuccess: (data) => {
            successMessage.value = 'Global Limit berhasil diperbarui'
            globalLimit.value = newGlobalLimit.value
            saving.value = false
            fetchSettings()
            fetchAuditLogs()
            setTimeout(() => { successMessage.value = null }, 3000)
        },
        onError: (err) => {
            error.value = err.message || 'Gagal menyimpan Global Limit'
            saving.value = false
            console.error(err)
        }
    })
}

function formatDateTime(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>
