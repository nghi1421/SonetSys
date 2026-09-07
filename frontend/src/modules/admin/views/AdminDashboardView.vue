<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Cloud, FileText, HardDrive, Image, Newspaper, Users, UsersRound } from '@lucide/vue'
import { formatBytes } from '@/shared/utils/formatBytes'
import { useAdminStore } from '../store/adminStore'

const adminStore = useAdminStore()
const { t } = useI18n()

const statCards = computed(() => [
  { label: t('admin.dashboard.stats.users'), value: adminStore.stats?.users ?? 0, icon: Users },
  { label: t('admin.dashboard.stats.posts'), value: adminStore.stats?.posts ?? 0, icon: Newspaper },
  { label: t('admin.dashboard.stats.groups'), value: adminStore.stats?.groups ?? 0, icon: UsersRound },
  { label: t('admin.dashboard.stats.media'), value: adminStore.stats?.media ?? 0, icon: Image },
  {
    label: t('admin.dashboard.stats.storage'),
    value: formatBytes(adminStore.stats?.storage_bytes ?? 0),
    icon: HardDrive,
  },
])

const quickLinks = computed(() => [
  { to: { name: 'admin-menu' }, label: t('admin.dashboard.links.menu.label'), description: t('admin.dashboard.links.menu.description'), icon: FileText },
  { to: { name: 'admin-storage-settings' }, label: t('admin.dashboard.links.storage.label'), description: t('admin.dashboard.links.storage.description'), icon: Cloud },
  { to: { name: 'admin-media-library' }, label: t('admin.dashboard.links.media.label'), description: t('admin.dashboard.links.media.description'), icon: Image },
  { to: { name: 'admin-users' }, label: t('admin.dashboard.links.users.label'), description: t('admin.dashboard.links.users.description'), icon: Users },
])

onMounted(() => {
  adminStore.fetchStats()
})
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('admin.dashboard.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('admin.dashboard.subtitle') }}</p>
    </div>

    <div
      v-if="adminStore.loadingStats"
      class="grid grid-cols-2 divide-x divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white sm:grid-cols-5 sm:divide-y-0"
    >
      <div v-for="i in 5" :key="i" class="h-20 animate-pulse bg-slate-50 p-4" />
    </div>

    <div
      v-else
      class="grid grid-cols-2 divide-x divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white shadow-sm sm:grid-cols-5 sm:divide-y-0"
    >
      <div v-for="card in statCards" :key="card.label" class="p-4">
        <div class="flex items-center gap-2 text-slate-400">
          <component :is="card.icon" class="h-4 w-4" />
 <p class="text-[11px] font-medium">{{ card.label }}</p>
        </div>
        <p class="mt-2 font-mono text-xl font-bold tabular-nums text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div>
 <h2 class="text-xs font-bold text-slate-500">{{ t('admin.dashboard.manageTitle') }}</h2>
      <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <RouterLink
          v-for="link in quickLinks"
          :key="link.label"
          :to="link.to"
          class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-blue-300 hover:bg-blue-50/40"
        >
          <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
            <component :is="link.icon" class="h-4 w-4" />
          </span>
          <span>
            <span class="block text-sm font-bold text-slate-900">{{ link.label }}</span>
            <span class="mt-0.5 block text-xs text-slate-500">{{ link.description }}</span>
          </span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
