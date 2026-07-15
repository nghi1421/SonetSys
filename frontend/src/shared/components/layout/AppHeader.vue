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
  <header class="border-b border-cyber-border bg-cyber-glass backdrop-blur-md">
    <div class="mx-auto flex max-w-2xl items-center justify-between px-4 py-3">
      <div>
        <h1
          class="bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink bg-clip-text text-sm font-bold uppercase tracking-widest text-transparent"
        >
          Sonetsys
        </h1>
        <p v-if="authStore.user" class="mt-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-neon-cyan">
          // {{ authStore.user.name }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <NotificationBell />
        <AppButton label="Log out" variant="secondary" @click="onLogout" />
      </div>
    </div>
  </header>
</template>
