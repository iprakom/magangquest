<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Onboarding Progress</h1>

                <!-- Status Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600">Current Status</span>
                        <span :class="statusClass" class="px-3 py-1 rounded-full text-sm font-medium">
                            {{ statusLabel }}
                        </span>
                    </div>
                </div>

                <!-- Checklist -->
                <div class="space-y-4">
                    <!-- Step 1: Login (Completed by default if user is logged in) -->
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">Login</h3>
                            <p class="text-sm text-gray-500">Sign in to your account</p>
                        </div>
                        <span class="text-green-500 text-sm font-medium">Completed</span>
                    </div>

                    <!-- Step 2: Upload Documents -->
                    <div class="flex items-center p-4 rounded-lg" :class="uploadStepClass">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-4" :class="uploadIconClass">
                            <span v-if="statusData.document_uploaded" class="text-white">✓</span>
                            <span v-else class="text-white">2</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">Upload Documents</h3>
                            <p class="text-sm text-gray-500">Submit your internship documents for verification</p>
                        </div>
                        <span v-if="statusData.document_uploaded" class="text-green-500 text-sm font-medium">Uploaded</span>
                        <Link v-else :href="route('onboarding.upload')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Upload →
                        </Link>
                    </div>

                    <!-- Step 3: Submit for Validation -->
                    <div class="flex items-center p-4 rounded-lg" :class="submitStepClass">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-4" :class="submitIconClass">
                            <span class="text-white">3</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">Submit for Validation</h3>
                            <p class="text-sm text-gray-500">Submit documents to admin for review</p>
                        </div>
                        <button 
                            v-if="statusData.can_submit"
                            @click="submitForValidation"
                            :disabled="submitting"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg disabled:opacity-50"
                        >
                            {{ submitting ? 'Submitting...' : 'Submit Now' }}
                        </button>
                        <span v-else-if="statusData.status === 'pending'" class="text-yellow-500 text-sm font-medium">
                            Awaiting Review
                        </span>
                        <span v-else class="text-gray-400 text-sm">Pending</span>
                    </div>

                    <!-- Step 4: Wait for Admin Validation -->
                    <div class="flex items-center p-4 rounded-lg" :class="waitStepClass">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-4" :class="waitIconClass">
                            <span class="text-white">4</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">Wait for Admin Validation</h3>
                            <p class="text-sm text-gray-500">Admin reviews and approves your documents</p>
                        </div>
                        <span v-if="statusData.is_active" class="text-green-500 text-sm font-medium">Approved</span>
                        <span v-else-if="statusData.status === 'pending'" class="text-yellow-500 text-sm font-medium animate-pulse">
                            In Review...
                        </span>
                        <span v-else class="text-gray-400 text-sm">Waiting</span>
                    </div>

                    <!-- Step 5: Get Active Status -->
                    <div class="flex items-center p-4 rounded-lg" :class="activeStepClass">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center mr-4" :class="activeIconClass">
                            <span v-if="statusData.is_active" class="text-white">✓</span>
                            <span v-else class="text-white">5</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-800">Get Active Status</h3>
                            <p class="text-sm text-gray-500">Complete onboarding and receive bonus points!</p>
                        </div>
                        <span v-if="statusData.is_active" class="text-green-500 text-sm font-medium">Active!</span>
                        <span v-else class="text-gray-400 text-sm">Locked</span>
                    </div>
                </div>

                <!-- Success Message -->
                <div v-if="statusData.is_active" class="mt-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">🎉</span>
                        <div>
                            <h3 class="font-medium text-green-800">Onboarding Complete!</h3>
                            <p class="text-sm text-green-600">You've received {{ bonusPoints }} bonus points. Start your quest!</p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    initialStatus: {
        type: Object,
        default: () => ({})
    }
})

const statusData = ref(props.initialStatus)
const submitting = ref(false)
const error = ref(null)
const bonusPoints = ref(100)

const statusLabel = computed(() => {
    const labels = {
        'restricted': 'Not Started',
        'pending': 'Awaiting Review',
        'active': 'Active',
        'frozen': 'Frozen'
    }
    return labels[statusData.value.status] || statusData.value.status
})

const statusClass = computed(() => {
    const classes = {
        'restricted': 'bg-gray-100 text-gray-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'active': 'bg-green-100 text-green-800',
        'frozen': 'bg-red-100 text-red-800'
    }
    return classes[statusData.value.status] || 'bg-gray-100 text-gray-800'
})

// Step styling helpers
const uploadStepClass = computed(() => statusData.value.document_uploaded ? 'bg-gray-50' : 'bg-white border border-gray-200')
const submitStepClass = computed(() => {
    if (statusData.value.can_submit) return 'bg-blue-50 border border-blue-200'
    if (statusData.value.status === 'pending') return 'bg-yellow-50 border border-yellow-200'
    return 'bg-gray-50'
})
const waitStepClass = computed(() => {
    if (statusData.value.status === 'pending') return 'bg-yellow-50 border border-yellow-200'
    if (statusData.value.is_active) return 'bg-green-50 border border-green-200'
    return 'bg-gray-50'
})
const activeStepClass = computed(() => statusData.value.is_active ? 'bg-green-50 border border-green-200' : 'bg-gray-50')

const uploadIconClass = computed(() => statusData.value.document_uploaded ? 'bg-green-500' : 'bg-blue-500')
const submitIconClass = computed(() => {
    if (statusData.value.can_submit) return 'bg-blue-500'
    if (statusData.value.status === 'pending') return 'bg-yellow-500'
    return 'bg-gray-400'
})
const waitIconClass = computed(() => {
    if (statusData.value.is_active) return 'bg-green-500'
    if (statusData.value.status === 'pending') return 'bg-yellow-500 animate-pulse'
    return 'bg-gray-400'
})
const activeIconClass = computed(() => statusData.value.is_active ? 'bg-green-500' : 'bg-gray-400')

const fetchStatus = async () => {
    try {
        const response = await fetch('/api/onboarding/status')
        const data = await response.json()
        statusData.value = data
    } catch (e) {
        console.error('Failed to fetch status:', e)
    }
}

const submitForValidation = async () => {
    submitting.value = true
    error.value = null

    try {
        const response = await fetch('/api/onboarding/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        })
        const data = await response.json()

        if (data.success) {
            statusData.value = {
                ...statusData.value,
                status: data.status
            }
        } else {
            error.value = data.message
        }
    } catch (e) {
        error.value = 'Failed to submit for validation'
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    fetchStatus()
})
</script>
