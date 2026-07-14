import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './app/router'
import { useThemeStore } from './shared/store/themeStore'

const app = createApp(App)

app.use(createPinia())
app.use(router)

useThemeStore()

app.mount('#app')
