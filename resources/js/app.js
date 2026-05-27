import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3'
import App from './App.vue'
import './bootstrap.css'

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./pages/*.vue')
    return pages[`./${name}.vue`]()
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .mount(el)
  },
})
