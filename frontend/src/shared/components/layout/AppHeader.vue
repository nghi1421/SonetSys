<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/modules/auth/store/authStore'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'

const router = useRouter()
const authStore = useAuthStore()

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
        <NotificationBell />
        <AppButton label="Log out" variant="secondary" @click="onLogout" />
      </div>
    </div>
  </header>
</template>
