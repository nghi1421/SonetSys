<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Menu } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'
import AppLocaleSwitcher from '@/shared/components/locale/AppLocaleSwitcher.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'

const emit = defineEmits<{ 'toggle-sidebar': [] }>()

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

async function onLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-cyber-border bg-cyber-glass backdrop-blur-md">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg sm:hidden"
          :aria-label="t('common.toggleMenu')"
          @click="emit('toggle-sidebar')"
        >
          <Menu class="h-4 w-4" />
        </button>
        <div>
          <h1
            class="bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink bg-clip-text text-sm font-bold uppercase tracking-widest text-transparent"
          >
            {{ t('common.siteName') }}
          </h1>
          <p v-if="authStore.user" class="mt-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-neon-cyan">
            // {{ authStore.user.name }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <AppLocaleSwitcher variant="dark" />
        <NotificationBell />
        <AppButton :label="t('common.logOut')" variant="secondary" @click="onLogout" />
      </div>
    </div>
  </header>
</template>
