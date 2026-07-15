<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { FileText, Home, Megaphone, Settings, Users } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useMenuStore } from '@/modules/menu/store/menuStore'
import type { MenuItem } from '@/modules/menu/types'

const authStore = useAuthStore()
const menuStore = useMenuStore()

const isAdmin = computed(() => authStore.user?.role.slug === 'tenant-admin')

onMounted(() => {
  menuStore.fetchMenu()
})

function iconFor(item: MenuItem) {
  if (item.is_home) return Home
  if (item.slug === 'group') return Users
  if (item.slug === 'advertise') return Megaphone
  return FileText
}

function targetFor(item: MenuItem) {
  if (item.is_home) return { name: 'dashboard' }
  if (item.slug === 'group') return { name: 'groups-list' }
  return { name: 'menu-page', params: { slug: item.slug } }
}
</script>

<template>
  <aside class="w-14 shrink-0 border-r border-cyber-border py-6 pr-2 sm:w-56 sm:pr-4">
    <nav class="space-y-1">
      <div v-if="menuStore.loading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-8 animate-pulse rounded-hud bg-cyber-surface/60" />
      </div>

      <RouterLink
        v-for="item in menuStore.items"
        :key="item.id"
        :to="targetFor(item)"
        :title="item.label"
        class="flex items-center justify-center gap-2.5 rounded-hud px-2 py-2 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan sm:justify-start sm:px-3"
        active-class="text-cyber-neon-cyan bg-cyber-glass border border-cyber-border shadow-cyan-glow"
      >
        <component :is="iconFor(item)" class="h-4 w-4 shrink-0" />
        <span class="hidden sm:inline">{{ item.label }}</span>
      </RouterLink>
    </nav>

    <RouterLink
      v-if="isAdmin"
      :to="{ name: 'admin-menu' }"
      title="Manage Menu"
      class="mt-6 flex items-center justify-center gap-2.5 border-t border-cyber-border px-2 pt-4 font-mono text-xs text-cyber-muted transition-all duration-300 hover:text-cyber-neon-indigo sm:justify-start sm:px-3"
      active-class="text-cyber-neon-indigo"
    >
      <Settings class="h-4 w-4 shrink-0" />
      <span class="hidden sm:inline">Manage Menu</span>
    </RouterLink>
  </aside>
</template>
