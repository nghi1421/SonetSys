<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  Clapperboard,
  FileText,
  Home,
  LayoutDashboard,
  LogOut,
  Megaphone,
  ShieldOff,
  UserRound,
  Users,
  Video,
} from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useMenuStore } from '@/modules/menu/store/menuStore'
import type { MenuItem } from '@/modules/menu/types'
import WalletBalancePill from '@/modules/wallet/components/WalletBalancePill.vue'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: [] }>()

const authStore = useAuthStore()
const menuStore = useMenuStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const isAdmin = computed(() => authStore.user?.role.slug === 'admin')

onMounted(() => {
  menuStore.fetchMenu()
})

watch(
  () => route.fullPath,
  () => emit('close'),
)

function iconFor(item: MenuItem) {
  if (item.is_home) return Home
  if (item.slug === 'group') return Users
  if (item.slug === 'advertise') return Megaphone
  return FileText
}

function targetFor(item: MenuItem) {
  if (item.is_home) return { name: 'dashboard' }
  if (item.slug === 'group') return { name: 'groups-list' }
  if (item.slug === 'advertise') return { name: 'advertise' }
  return { name: 'menu-page', params: { slug: item.slug } }
}

async function onLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div
    v-if="props.open"
    class="fixed inset-x-0 bottom-0 top-16 z-10 bg-cyber-bg/70 backdrop-blur-sm sm:hidden"
    @click="emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 top-16 z-20 w-56 -translate-x-full overflow-y-auto border-r border-cyber-border bg-cyber-glass py-6 pr-2 pl-4 backdrop-blur-md transition-transform duration-300 sm:sticky sm:top-16 sm:z-auto sm:w-56 sm:max-h-[calc(100vh-4rem)] sm:translate-x-0 sm:self-start sm:px-4"
    :class="props.open && 'translate-x-0'"
  >
    <RouterLink
      v-if="authStore.user"
      :to="{ name: 'user-profile', params: { id: authStore.user.id } }"
      :title="t('common.viewProfile')"
      class="mb-4 flex items-center gap-2.5 rounded-hud border border-cyber-border bg-cyber-glass px-3 py-2.5 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
    >
      <img
        v-if="authStore.user.avatar_url"
        :src="authStore.user.avatar_url"
        :alt="authStore.user.name"
        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-cyber-neon-indigo"
      />
      <span
        v-else
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyber-surface ring-2 ring-cyber-neon-indigo"
      >
        <UserRound class="h-4 w-4 text-cyber-neon-cyan" />
      </span>
      <span class="min-w-0">
        <span class="block truncate font-mono text-xs font-bold text-cyber-text">{{ authStore.user.name }}</span>
        <WalletBalancePill/>
      </span>
    </RouterLink>

    <nav class="space-y-1">
      <div v-if="menuStore.loading && menuStore.items.length === 0" class="space-y-2">
        <div
          v-for="i in 3"
          :key="i"
          class="h-8 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 backdrop-blur-md"
        />
      </div>

      <RouterLink
        v-for="item in menuStore.items"
        :key="item.id"
        :to="targetFor(item)"
        :title="item.label"
        class="flex items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
        active-class="text-cyber-neon-cyan bg-cyber-glass border border-cyber-border shadow-cyan-glow"
      >
        <component :is="iconFor(item)" class="h-4 w-4 shrink-0" />
        <span>{{ item.label }}</span>
      </RouterLink>

      <RouterLink
        :to="{ name: 'stories' }"
        :title="t('common.stories')"
        class="flex items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
        active-class="text-cyber-neon-cyan bg-cyber-glass border border-cyber-border shadow-cyan-glow"
      >
        <Clapperboard class="h-4 w-4 shrink-0" />
        <span>{{ t('common.stories') }}</span>
      </RouterLink>

      <RouterLink
        :to="{ name: 'reels' }"
        :title="t('common.reels')"
        class="flex items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
        active-class="text-cyber-neon-cyan bg-cyber-glass border border-cyber-border shadow-cyan-glow"
      >
        <Video class="h-4 w-4 shrink-0" />
        <span>{{ t('common.reels') }}</span>
      </RouterLink>

      <RouterLink
        :to="{ name: 'blocked-users' }"
        :title="t('common.blockedUsers')"
        class="flex items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
        active-class="text-cyber-neon-cyan bg-cyber-glass border border-cyber-border shadow-cyan-glow"
      >
        <ShieldOff class="h-4 w-4 shrink-0" />
        <span>{{ t('common.blockedUsers') }}</span>
      </RouterLink>
    </nav>

    <div class="mt-6 space-y-1 border-t border-cyber-border pt-4">
      <RouterLink
        v-if="isAdmin"
        :to="{ name: 'admin-dashboard' }"
        :title="t('common.adminPanel')"
        class="flex items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs font-bold uppercase tracking-wider text-cyber-neon-indigo transition-all duration-300 hover:text-cyber-neon-cyan"
      >
        <LayoutDashboard class="h-4 w-4 shrink-0" />
        <span>{{ t('common.adminPanel') }}</span>
      </RouterLink>

      <button
        type="button"
        class="flex w-full items-center gap-2.5 rounded-hud px-3 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-pink"
        @click="onLogout"
      >
        <LogOut class="h-4 w-4 shrink-0" />
        <span>{{ t('common.logOut') }}</span>
      </button>
    </div>
  </aside>
</template>
