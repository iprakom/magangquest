import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3'
import axios from 'axios'

// Make axios globally available
window.axios = axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

// Import the AppLayout
import AppLayout from '@/layouts/AppLayout.vue'

// Create Pinia instance
const pinia = createPinia()

// Create Inertia app
createInertiaApp({
    title: (title) => title ? `${title} - Magang Quest` : 'Magang Quest',
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue')
        const page = pages[`./pages/${name}.vue`]()
        
        return page.then((module) => {
            // Only apply layout for authenticated pages (those with auth data)
            // Skip layout for login, onboarding, and welcome pages
            const publicPages = ['Login', 'Welcome', 'Onboarding', 'OnboardingUpload']
            
            if (publicPages.includes(name)) {
                return module
            }
            
            return {
                ...module,
                layout: AppLayout,
            }
        })
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el)
    },
})
