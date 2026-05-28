<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Admin - Validasi Onboarding</h1>

                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button
                        @click="activeTab = 'pending'"
                        :class="['px-6 py-3 font-medium', activeTab === 'pending' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
                    >
                        Pending ({{ pendingUsers.length }})
                    </button>
                    <button
                        @click="activeTab = 'all'"
                        :class="['px-6 py-3 font-medium', activeTab === 'all' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
                    >
                        Semua Intern
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-8">
                    <p class="text-gray-500">Memuat data...</p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>

                <!-- Pending Users Tab -->
                <div v-if="activeTab === 'pending'">
                    <div v-if="pendingUsers.length === 0" class="text-center py-8">
                        <div class="text-4xl mb-3">✅</div>
                        <p class="text-gray-500">Tidak ada pengguna yang menunggu validasi</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="user in pendingUsers"
                            :key="user.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="font-semibold text-gray-800">{{ user.name }}</h3>
                                        <span :class="getInternTypeBadgeClass(user.intern_type)" class="px-2 py-1 rounded text-xs font-medium">
                                            {{ formatInternType(user.intern_type) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">{{ user.email }}</p>
                                    <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                        <span v-if="user.nip">NIP: {{ user.nip }}</span>
                                        <span v-if="user.unit_kerja">Unit: {{ user.unit_kerja }}</span>
                                        <span v-if="user.room">Room: {{ user.room }}</span>
                                        <span>Start: {{ user.start_date }}</span>
                                        <span>End: {{ user.end_date }}</span>
                                    </div>
                                    <div v-if="user.document_path" class="mt-3">
                                        <a
                                            :href="'/storage/' + user.document_path"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-600 rounded text-sm hover:bg-blue-100"
                                        >
                                            📄 Lihat Dokumen
                                        </a>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <button
                                        @click="approveUser(user.id)"
                                        :disabled="processingId === user.id"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded disabled:opacity-50"
                                    >
                                        {{ processingId === user.id ? '...' : '✓ Approve' }}
                                    </button>
                                    <button
                                        @click="showRejectModal(user.id)"
                                        :disabled="processingId === user.id"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded disabled:opacity-50"
                                    >
                                        ✕ Tolak
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Interns Tab -->
                <div v-if="activeTab === 'all'">
                    <div class="mb-4">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-sm font-medium text-gray-600">Nama</th>
                                    <th class="px-4 py-3 text-sm font-medium text-gray-600">Tipe</th>
                                    <th class="px-4 py-3 text-sm font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-sm font-medium text-gray-600">Periode</th>
                                    <th class="px-4 py-3 text-sm font-medium text-gray-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="user in filteredAllUsers" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="getInternTypeBadgeClass(user.intern_type)" class="px-2 py-1 rounded text-xs font-medium">
                                            {{ formatInternType(user.intern_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusBadgeClass(user.onboarding_status)" class="px-2 py-1 rounded text-xs font-medium">
                                            {{ formatStatus(user.onboarding_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ user.start_date }} - {{ user.end_date }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            v-if="user.onboarding_status === 'pending'"
                                            @click="approveUser(user.id)"
                                            :disabled="processingId === user.id"
                                            class="text-green-600 hover:text-green-800 text-sm disabled:opacity-50"
                                        >
                                            Approve
                                        </button>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectModalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tolak Pendaftaran</h3>
                <p class="text-sm text-gray-600 mb-4">Berikan alasan penolakan:</p>
                <textarea
                    v-model="rejectReason"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-4"
                    placeholder="Contoh: Dokumen tidak jelas, tanggal magang tidak valid, dll."
                ></textarea>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="cancelReject"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded"
                    >
                        Batal
                    </button>
                    <button
                        @click="confirmReject"
                        :disabled="!rejectReason.trim() || processingId"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded disabled:opacity-50"
                    >
                        Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    pendingUsers: {
        type: Array,
        default: () => []
    },
    allUsers: {
        type: Array,
        default: () => []
    }
})

const loading = ref(false)
const error = ref(null)
const activeTab = ref('pending')
const searchQuery = ref('')
const processingId = ref(null)
const rejectModalVisible = ref(false)
const rejectingUserId = ref(null)
const rejectReason = ref('')

const filteredAllUsers = computed(() => {
    if (!searchQuery.value) return props.allUsers
    const query = searchQuery.value.toLowerCase()
    return props.allUsers.filter(user =>
        user.name?.toLowerCase().includes(query) ||
        user.email?.toLowerCase().includes(query)
    )
})

const formatInternType = (type) => {
    const types = {
        'sma_smk': 'SMA/SMK',
        'mahasiswa': 'Mahasiswa',
        'profesional': 'Profesional'
    }
    return types[type] || type
}

const getInternTypeBadgeClass = (type) => {
    const classes = {
        'sma_smk': 'bg-purple-100 text-purple-800',
        'mahasiswa': 'bg-blue-100 text-blue-800',
        'profesional': 'bg-green-100 text-green-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
    const statuses = {
        'restricted': 'Restricted',
        'pending': 'Pending',
        'active': 'Active',
        'frozen': 'Frozen'
    }
    return statuses[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'restricted': 'bg-yellow-100 text-yellow-800',
        'pending': 'bg-orange-100 text-orange-800',
        'active': 'bg-green-100 text-green-800',
        'frozen': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const approveUser = async (userId) => {
    if (!confirm('Approve pengguna ini?')) return

    processingId.value = userId
    error.value = null

    try {
        const response = await fetch(`/api/admin/onboarding/${userId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        })
        const data = await response.json()

        if (data.success) {
            alert(`Berhasil approve! User mendapat ${data.bonus_points} poin bonus.`)
            router.visit('/admin/onboarding', { preserveScroll: true })
        } else {
            error.value = data.message
        }
    } catch (e) {
        error.value = 'Gagal approve pengguna.'
    } finally {
        processingId.value = null
    }
}

const showRejectModal = (userId) => {
    rejectingUserId.value = userId
    rejectReason.value = ''
    rejectModalVisible.value = true
}

const cancelReject = () => {
    rejectModalVisible.value = false
    rejectingUserId.value = null
    rejectReason.value = ''
}

const confirmReject = async () => {
    if (!rejectReason.value.trim()) return

    processingId.value = rejectingUserId.value
    error.value = null

    try {
        const response = await fetch(`/api/admin/onboarding/${rejectingUserId.value}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ reason: rejectReason.value })
        })
        const data = await response.json()

        if (data.success) {
            alert('Pengguna ditolak.')
            cancelReject()
            router.visit('/admin/onboarding', { preserveScroll: true })
        } else {
            error.value = data.message
        }
    } catch (e) {
        error.value = 'Gagal menolak pengguna.'
    } finally {
        processingId.value = null
    }
}
</script>
