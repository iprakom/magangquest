<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Onboarding - Complete Your Profile</h1>

                <!-- Error Message -->
                <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>

                <!-- Success Message -->
                <div v-if="success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-green-500 text-xl mr-2">✓</span>
                        <p class="text-green-700">{{ success }}</p>
                    </div>
                </div>

                <!-- Profile Form -->
                <form @submit.prevent="submitForm">
                    <div class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan nama lengkap Anda"
                            />
                        </div>

                        <!-- NIP -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                NIP
                            </label>
                            <input
                                v-model="form.nip"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan NIP Anda (jika ada)"
                            />
                        </div>

                        <!-- Unit Kerja -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Unit Kerja
                            </label>
                            <input
                                v-model="form.unit_kerja"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan unit kerja Anda"
                            />
                        </div>

                        <!-- Tanggal Mulai Magang -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Mulai Magang <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <!-- Tanggal Selesai Magang -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Selesai Magang <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <!-- Room Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Room
                            </label>
                            <select
                                v-model="form.room"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Pilih Room</option>
                                <option v-for="room in rooms" :key="room" :value="room">{{ room }}</option>
                            </select>
                        </div>

                        <!-- Tipe Magang -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipe Magang <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.intern_type"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Pilih Tipe Magang</option>
                                <option value="sma_smk">SMA/SMK</option>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="profesional">Profesional</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-8 flex gap-4">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Simpan & Lanjutkan' }}
                        </button>
                        <Link
                            href="/onboarding/upload"
                            class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg"
                        >
                            Upload Document
                        </Link>
                    </div>
                </form>

                <!-- Document Upload Status -->
                <div v-if="statusData.document_uploaded" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-green-500 text-xl mr-2">✓</span>
                        <p class="text-green-700">Dokumen sudah diupload</p>
                    </div>
                </div>
                <div v-else class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-700 text-sm">
                        Mohon upload dokumen magang Anda setelah mengisi form ini.
                    </p>
                    <Link
                        href="/onboarding/upload"
                        class="mt-2 inline-block text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                        Upload Document →
                    </Link>
                </div>

                <!-- Submit for Validation Button -->
                <div v-if="statusData.can_submit" class="mt-6">
                    <button
                        @click="submitForValidation"
                        :disabled="submittingValidation"
                        class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ submittingValidation ? 'Mengirim...' : 'Submit untuk Validasi' }}
                    </button>
                </div>

                <!-- Pending Status -->
                <div v-if="statusData.status === 'pending'" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-700 text-center">
                        Menunggu validasi dari admin...
                    </p>
                </div>

                <!-- Active Status -->
                <div v-if="statusData.is_active" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-center">
                        <span class="text-2xl mr-3">🎉</span>
                        <div>
                            <h3 class="font-medium text-green-800">Onboarding Selesai!</h3>
                            <p class="text-sm text-green-600">Anda menerima {{ bonusPoints }} bonus points. Mulai quest Anda!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    initialStatus: {
        type: Object,
        default: () => ({})
    },
    user: {
        type: Object,
        default: () => ({})
    }
})

const statusData = ref(props.initialStatus)
const submitting = ref(false)
const submittingValidation = ref(false)
const error = ref(null)
const success = ref(null)
const bonusPoints = ref(50)

const rooms = [
    'Room A - Jakarta',
    'Room B - Surabaya',
    'Room C - Bandung',
    'Room D - Medan',
    'Room E - Makassar',
    'Room F - Yogyakarta',
    'Room G - Semarang',
    'Room H - Palembang'
]

const form = reactive({
    name: props.user.name || '',
    nip: props.user.nip || '',
    unit_kerja: props.user.unit_kerja || '',
    start_date: props.user.start_date || '',
    end_date: props.user.end_date || '',
    room: props.user.room || '',
    intern_type: props.user.intern_type || ''
})

const fetchStatus = async () => {
    try {
        const response = await fetch('/api/onboarding/status')
        const data = await response.json()
        statusData.value = data

        // Pre-fill form with user data if available
        if (data.user) {
            form.name = data.user.name || form.name
            form.nip = data.user.nip || form.nip
            form.unit_kerja = data.user.unit_kerja || form.unit_kerja
            form.start_date = data.user.start_date || form.start_date
            form.end_date = data.user.end_date || form.end_date
            form.room = data.user.room || form.room
            form.intern_type = data.user.intern_type || form.intern_type
        }
    } catch (e) {
        console.error('Failed to fetch status:', e)
    }
}

const submitForm = async () => {
    submitting.value = true
    error.value = null
    success.value = null

    try {
        const response = await fetch('/api/onboarding/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(form)
        })
        const data = await response.json()

        if (data.success) {
            success.value = data.message
            setTimeout(() => {
                success.value = null
            }, 3000)
        } else {
            error.value = data.message
        }
    } catch (e) {
        error.value = 'Gagal menyimpan data. Silakan coba lagi.'
    } finally {
        submitting.value = false
    }
}

const submitForValidation = async () => {
    submittingValidation.value = true
    error.value = null

    try {
        const response = await fetch('/api/onboarding/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        })
        const data = await response.json()

        if (data.success) {
            statusData.value.status = data.status
            success.value = 'Berhasil dikirim untuk validasi!'
            setTimeout(() => {
                fetchStatus()
            }, 1500)
        } else {
            error.value = data.message
        }
    } catch (e) {
        error.value = 'Gagal mengirim untuk validasi.'
    } finally {
        submittingValidation.value = false
    }
}

onMounted(() => {
    fetchStatus()
})
</script>
