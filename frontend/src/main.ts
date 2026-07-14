import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './app/router'
import { useAuthStore } from './modules/auth/store/authStore'
import { useThemeStore } from './shared/store/themeStore'

const app = createApp(App)

app.use(createPinia())
app.use(router)

useThemeStore()

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

  app.mount('#app')
}

bootstrap()
