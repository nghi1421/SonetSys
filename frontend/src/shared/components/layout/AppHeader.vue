<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Menu, Search } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import MessageBell from '@/modules/chat/components/MessageBell.vue'
import NotificationBell from '@/modules/notifications/components/NotificationBell.vue'
import WalletBalancePill from '@/modules/wallet/components/WalletBalancePill.vue'
import AppLocaleSwitcher from '@/shared/components/locale/AppLocaleSwitcher.vue'
import AppThemeSwitcher from '@/shared/components/theme/AppThemeSwitcher.vue'

const emit = defineEmits<{ 'toggle-sidebar': [] }>()

const authStore = useAuthStore()
const { t } = useI18n()
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
        <AppThemeSwitcher />
        <AppLocaleSwitcher variant="dark" />
        <router-link
          :to="{ name: 'search' }"
          class="rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
          :aria-label="t('search.headerIcon.ariaLabel')"
        >
          <Search class="h-4 w-4" />
        </router-link>
        <WalletBalancePill />
        <MessageBell />
        <NotificationBell />
      </div>
    </div>
  </header>
</template>
