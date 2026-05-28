<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 py-8">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white">📅 Kalender Hari Libur</h1>
                    <p class="text-slate-400 text-sm mt-1">Kelola hari libur untuk perhitungan SLA dan penalti</p>
                </div>
                <button
                    @click="showForm = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <span>➕</span> Tambah Libur
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-slate-400 text-sm">Tahun:</label>
                        <select
                            v-model="selectedYear"
                            @change="fetchHolidays"
                            class="bg-slate-700 text-white rounded px-3 py-1.5 text-sm border border-slate-600 focus:border-blue-500 focus:outline-none"
                        >
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-slate-400 text-sm">Tipe:</label>
                        <select
                            v-model="selectedType"
                            @change="fetchHolidays"
                            class="bg-slate-700 text-white rounded px-3 py-1.5 text-sm border border-slate-600 focus:border-blue-500 focus:outline-none"
                        >
                            <option value="">Semua</option>
                            <option value="national">Nasional</option>
                            <option value="local">Lokal</option>
                            <option value="company">Perusahaan</option>
                        </select>
                    </div>
                    <button
                        @click="fetchHolidays"
                        class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white rounded text-sm transition-colors"
                    >
                        🔄 Refresh
                    </button>
                </div>
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

            <!-- Holidays Table -->
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Berulang</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <tr v-if="holidays.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                Tidak ada hari libur untuk tahun ini
                            </td>
                        </tr>
                        <tr
                            v-for="holiday in holidays"
                            :key="holiday.id"
                            class="hover:bg-slate-700/30 transition-colors"
                        >
                            <td class="px-4 py-3 text-white text-sm">{{ formatDate(holiday.date) }}</td>
                            <td class="px-4 py-3 text-white text-sm font-medium">{{ holiday.name }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="getTypeBadgeClass(holiday.type)" class="px-2 py-1 rounded text-xs font-medium">
                                    {{ getTypeLabel(holiday.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-400">
                                {{ holiday.is_recurring ? '✓ Ya' : 'Tidak' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    @click="editHoliday(holiday)"
                                    class="text-blue-400 hover:text-blue-300 text-sm mr-3"
                                >
                                    ✏️ Edit
                                </button>
                                <button
                                    @click="confirmDelete(holiday)"
                                    class="text-red-400 hover:text-red-300 text-sm"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Info Box -->
            <div class="mt-6 bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 p-4">
                <h3 class="text-white font-medium mb-2">ℹ️ Informasi</h3>
                <ul class="text-slate-400 text-sm space-y-1">
                    <li>• Hari libur digunakan dalam perhitungan SLA (3x24 jam)</li>
                    <li>• Hari libur mempengaruhi penalti hoarding (10 hari kerja)</li>
                    <li>• Hari libur mempengaruhi countdown endgame</li>
                </ul>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeForm">
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6 w-full max-w-md mx-4">
                <h2 class="text-xl font-bold text-white mb-4">
                    {{ editingHoliday ? '✏️ Edit Hari Libur' : '➕ Tambah Hari Libur' }}
                </h2>

                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-slate-300 text-sm font-medium mb-1">Tanggal</label>
                            <input
                                v-model="formData.date"
                                type="date"
                                required
                                class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-slate-300 text-sm font-medium mb-1">Nama</label>
                            <input
                                v-model="formData.name"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="Nama hari libur"
                                class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-slate-300 text-sm font-medium mb-1">Tipe</label>
                            <select
                                v-model="formData.type"
                                required
                                class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-blue-500 focus:outline-none"
                            >
                                <option value="national">Nasional</option>
                                <option value="local">Lokal</option>
                                <option value="company">Perusahaan</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                v-model="formData.is_recurring"
                                type="checkbox"
                                id="is_recurring"
                                class="w-4 h-4 rounded bg-slate-700 border-slate-600 text-blue-500 focus:ring-blue-500 focus:ring-offset-slate-800"
                            />
                            <label for="is_recurring" class="text-slate-300 text-sm">Berulang setiap tahun</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="closeForm"
                            class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6 w-full max-w-md mx-4">
                <h2 class="text-xl font-bold text-white mb-4">🗑️ Konfirmasi Hapus</h2>
                <p class="text-slate-300 mb-6">
                    Yakin ingin menghapus hari libur <strong>{{ holidayToDelete?.name }}</strong> pada tanggal <strong>{{ formatDate(holidayToDelete?.date) }}</strong>?
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteConfirm = false"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="deleteHoliday"
                        :disabled="deleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ deleting ? 'Menghapus...' : 'Hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const holidays = ref([])
const showForm = ref(false)
const showDeleteConfirm = ref(false)
const editingHoliday = ref(null)
const holidayToDelete = ref(null)
const submitting = ref(false)
const deleting = ref(false)

const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const selectedType = ref('')
const availableYears = computed(() => {
    const years = []
    for (let y = currentYear - 2; y <= currentYear + 2; y++) {
        years.push(y)
    }
    return years
})

const formData = ref({
    date: '',
    name: '',
    type: 'national',
    is_recurring: false
})

onMounted(() => {
    fetchHolidays()
})

function fetchHolidays() {
    loading.value = true
    error.value = null

    const params = { year: selectedYear.value }
    if (selectedType.value) {
        params.type = selectedType.value
    }

    router.get('/api/admin/holidays', params, {
        preserveScroll: true,
        onSuccess: (data) => {
            holidays.value = data.holidays || []
            loading.value = false
        },
        onError: (err) => {
            error.value = 'Gagal memuat data hari libur'
            loading.value = false
            console.error(err)
        }
    })
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

function getTypeBadgeClass(type) {
    const classes = {
        national: 'bg-red-900/50 text-red-300',
        local: 'bg-yellow-900/50 text-yellow-300',
        company: 'bg-blue-900/50 text-blue-300'
    }
    return classes[type] || 'bg-slate-700 text-slate-300'
}

function getTypeLabel(type) {
    const labels = {
        national: 'Nasional',
        local: 'Lokal',
        company: 'Perusahaan'
    }
    return labels[type] || type
}

function editHoliday(holiday) {
    editingHoliday.value = holiday
    formData.value = {
        date: holiday.date,
        name: holiday.name,
        type: holiday.type,
        is_recurring: holiday.is_recurring
    }
    showForm.value = true
}

function closeForm() {
    showForm.value = false
    editingHoliday.value = null
    formData.value = {
        date: '',
        name: '',
        type: 'national',
        is_recurring: false
    }
}

function submitForm() {
    submitting.value = true
    error.value = null
    successMessage.value = null

    const url = editingHoliday.value
        ? `/api/admin/holidays/${editingHoliday.value.id}`
        : '/api/admin/holidays'

    const method = editingHoliday.value ? 'put' : 'post'

    router[method](url, formData.value, {
        preserveScroll: true,
        onSuccess: (data) => {
            successMessage.value = editingHoliday.value
                ? 'Hari libur berhasil diperbarui'
                : 'Hari libur berhasil ditambahkan'
            submitting.value = false
            closeForm()
            fetchHolidays()
            setTimeout(() => { successMessage.value = null }, 3000)
        },
        onError: (err) => {
            error.value = err.message || 'Gagal menyimpan hari libur'
            submitting.value = false
            console.error(err)
        }
    })
}

function confirmDelete(holiday) {
    holidayToDelete.value = holiday
    showDeleteConfirm.value = true
}

function deleteHoliday() {
    if (!holidayToDelete.value) return

    deleting.value = true
    error.value = null

    router.delete(`/api/admin/holidays/${holidayToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Hari libur berhasil dihapus'
            deleting.value = false
            showDeleteConfirm.value = false
            holidayToDelete.value = null
            fetchHolidays()
            setTimeout(() => { successMessage.value = null }, 3000)
        },
        onError: (err) => {
            error.value = err.message || 'Gagal menghapus hari libur'
            deleting.value = false
            console.error(err)
        }
    })
}
</script>
