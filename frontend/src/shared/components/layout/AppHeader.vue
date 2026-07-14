<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/modules/auth/store/authStore'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useThemeStore } from '@/shared/store/themeStore'

const router = useRouter()
const authStore = useAuthStore()
const themeStore = useThemeStore()

async function onLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto flex max-w-2xl items-center justify-between px-4 py-3">
      <div>
        <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-zinc-100">Sonetsys</h1>
        <p v-if="authStore.user" class="text-xs text-slate-500 dark:text-zinc-400">{{ authStore.user.name }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-md p-2 text-slate-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:text-zinc-400 dark:hover:bg-zinc-800"
          :aria-label="themeStore.theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="themeStore.toggleTheme"
        >
          <Sun v-if="themeStore.theme === 'dark'" class="h-5 w-5" />
          <Moon v-else class="h-5 w-5" />
        </button>
        <NotificationBell />
        <AppButton label="Log out" variant="secondary" @click="onLogout" />
      </div>
    </div>
  </header>
</template>
