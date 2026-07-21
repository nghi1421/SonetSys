import '@fontsource/inter/400.css'
import '@fontsource/inter/500.css'
import '@fontsource/inter/600.css'
import '@fontsource/inter/700.css'
import '@fontsource/jetbrains-mono/400.css'
import '@fontsource/jetbrains-mono/500.css'
import '@fontsource/jetbrains-mono/700.css'
import './assets/main.css'
import 'leaflet/dist/leaflet.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './app/router'
import { i18n } from './i18n'
import { useAuthStore } from './modules/auth/store/authStore'

const app = createApp(App)

app.use(createPinia())
app.use(i18n)

async function bootstrap(): Promise<void> {
  const authStore = useAuthStore()

  // A stored token survives a page reload, but the in-memory user
  // profile doesn't — refetch it so ownership checks (edit/delete,
  // moderation) work immediately instead of only after a fresh login.
  if (authStore.isAuthenticated) {
    try {
      await authStore.fetchCurrentUser()
    } catch {
      authStore.clearSession()
    }
  }

  // Installing the router (and thus its initial, guard-driven navigation)
  // only after the user profile resolves avoids a race where a role-based
  // guard (requiresAdmin) runs before authStore.user is populated.
  app.use(router)
  app.mount('#app')
}

bootstrap()
