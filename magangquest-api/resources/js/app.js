import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3'

// Create Pinia instance
const pinia = createPinia()

// Create Inertia app
createInertiaApp({
    title: (title) => title ? `${title} - Magang Quest` : 'Magang Quest',
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue')
        return pages[`./${name}.vue`]()
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el)
    },
})
