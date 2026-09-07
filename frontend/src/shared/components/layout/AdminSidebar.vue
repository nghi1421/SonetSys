<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Cloud, Crown, FileText, Flag, Image, LayoutDashboard, Megaphone, Music, Settings, Smile, Users, Wallet } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: [] }>()

const route = useRoute()
const authStore = useAuthStore()
const { t } = useI18n()

// Moderators can only reach the Reports page (see requiresStaff in the
// router) — the rest of the /admin panel stays Admin-only, so the sidebar
// only lists what a Moderator can actually open.
const isModerator = computed(() => authStore.user?.role.slug === 'moderator')

const navItems = computed(() => {
  const items = [
    { to: { name: 'admin-dashboard' }, label: t('common.nav.dashboard'), icon: LayoutDashboard, adminOnly: true },
    { to: { name: 'admin-menu' }, label: t('common.nav.menuPages'), icon: FileText, adminOnly: true },
    { to: { name: 'admin-storage-settings' }, label: t('common.nav.storage'), icon: Cloud, adminOnly: true },
    { to: { name: 'admin-media-library' }, label: t('common.nav.mediaLibrary'), icon: Image, adminOnly: true },
    { to: { name: 'admin-users' }, label: t('common.nav.users'), icon: Users, adminOnly: true },
    { to: { name: 'admin-wallets' }, label: t('common.nav.wallets'), icon: Wallet, adminOnly: true },
    { to: { name: 'admin-ads' }, label: t('common.nav.ads'), icon: Megaphone, adminOnly: true },
    { to: { name: 'admin-subscriptions' }, label: t('common.nav.subscriptions'), icon: Crown, adminOnly: true },
    { to: { name: 'admin-reports' }, label: t('common.nav.reports'), icon: Flag, adminOnly: false },
    { to: { name: 'admin-settings' }, label: t('common.nav.settings'), icon: Settings, adminOnly: true },
    { to: { name: 'admin-reactions' }, label: t('common.nav.reactions'), icon: Smile, adminOnly: true },
    { to: { name: 'admin-songs' }, label: t('common.nav.songs'), icon: Music, adminOnly: true },
  ]

  return isModerator.value ? items.filter((item) => !item.adminOnly) : items
})

watch(
  () => route.fullPath,
  () => emit('close'),
)
</script>

<template>
  <div
    v-if="props.open"
    class="fixed inset-x-0 bottom-0 top-16 z-10 bg-slate-900/20 sm:hidden"
    @click="emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 top-16 z-20 w-56 -translate-x-full overflow-y-auto border-r border-slate-200 bg-white py-6 pr-2 pl-4 transition-transform duration-300 sm:static sm:z-auto sm:w-56 sm:translate-x-0 sm:pr-4 sm:pl-0"
    :class="props.open && 'translate-x-0'"
  >
    <nav class="space-y-1">
      <RouterLink
        v-for="item in navItems"
        :key="item.label"
        :to="item.to"
        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-500 transition-colors duration-200 hover:bg-slate-50 hover:text-slate-900"
        active-class="text-blue-700 bg-blue-50"
        exact-active-class="text-blue-700 bg-blue-50"
      >
        <component :is="item.icon" class="h-4 w-4 shrink-0" />
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>
