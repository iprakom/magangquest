<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Upload Documents</h1>
                    <Link href="/onboarding" class="text-gray-600 hover:text-gray-800 text-sm">
                        ← Back to Onboarding
                    </Link>
                </div>

                <!-- Upload Form -->
                <form @submit.prevent="handleUpload">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload your internship documents (PDF, JPG, PNG)
                        </label>
                        <div 
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors"
                            :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <input 
                                type="file" 
                                ref="fileInput"
                                @change="handleFileSelect"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="hidden"
                            />
                            
                            <div v-if="!selectedFile" class="cursor-pointer" @click="$refs.fileInput.click()">
                                <div class="text-4xl mb-3">📄</div>
                                <p class="text-gray-600 mb-1">Drag and drop your file here</p>
                                <p class="text-gray-400 text-sm">or click to browse</p>
                            </div>

                            <div v-else class="flex items-center justify-center">
                                <div class="text-4xl mr-3">✓</div>
                                <div class="text-left">
                                    <p class="font-medium text-gray-800">{{ selectedFile.name }}</p>
                                    <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                                </div>
                                <button 
                                    type="button"
                                    @click="clearFile"
                                    class="ml-4 text-red-500 hover:text-red-700"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div v-if="uploading" class="mb-6">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">Uploading...</span>
                            <span class="text-sm text-gray-600">{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-blue-600 h-2 rounded-full transition-all"
                                :style="{ width: uploadProgress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-600 text-sm">{{ error }}</p>
                    </div>

                    <!-- Success -->
                    <div v-if="success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <p class="text-green-700">{{ success }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4">
                        <button 
                            type="submit"
                            :disabled="!selectedFile || uploading"
                            class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ uploading ? 'Uploading...' : 'Upload Document' }}
                        </button>
                        <Link 
                            href="/onboarding"
                            class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const fileInput = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref(null)
const success = ref(null)

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        validateAndSetFile(file)
    }
}

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file) {
        validateAndSetFile(file)
    }
}

const validateAndSetFile = (file) => {
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
    const maxSize = 10 * 1024 * 1024 // 10MB

    if (!allowedTypes.includes(file.type)) {
        error.value = 'Invalid file type. Please upload PDF, JPG, or PNG.'
        return
    }

    if (file.size > maxSize) {
        error.value = 'File size exceeds 10MB limit.'
        return
    }

    error.value = null
    selectedFile.value = file
}

const clearFile = () => {
    selectedFile.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const handleUpload = async () => {
    if (!selectedFile.value) return

    uploading.value = true
    uploadProgress.value = 0
    error.value = null
    success.value = null

    const formData = new FormData()
    formData.append('document', selectedFile.value)

    try {
        // Simulate progress for UX (actual progress would need XMLHttpRequest)
        const progressInterval = setInterval(() => {
            if (uploadProgress.value < 90) {
                uploadProgress.value += 10
            }
        }, 100)

        const response = await fetch('/api/onboarding/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: formData
        })

        clearInterval(progressInterval)
        uploadProgress.value = 100

        const data = await response.json()

        if (data.success) {
            success.value = data.message
            setTimeout(() => {
                router.visit('/onboarding')
            }, 1500)
        } else {
            error.value = data.message || 'Upload failed'
            uploadProgress.value = 0
        }
    } catch (e) {
        error.value = 'Failed to upload document. Please try again.'
        uploadProgress.value = 0
    } finally {
        uploading.value = false
    }
}

onMounted(() => {
    // Check if user can upload
})
</script>
