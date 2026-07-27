<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useUserStore } from '../store/userStore'
import type { User } from '@/modules/auth/types'

const authStore = useAuthStore()
const userStore = useUserStore()
const { t } = useI18n()
const page = ref(1)
const savingUserId = ref<number | null>(null)
const rowError = ref<string | null>(null)

const ROLE_OPTIONS = computed(() => [
  { value: 'admin', label: t('admin.users.roles.admin') },
  { value: 'moderator', label: t('admin.users.roles.moderator') },
  { value: 'user', label: t('admin.users.roles.user') },
])

const STATUS_OPTIONS = computed(() => [
  { value: 'active', label: t('admin.users.statuses.active') },
  { value: 'suspended', label: t('admin.users.statuses.suspended') },
  { value: 'banned', label: t('admin.users.statuses.banned') },
])

const totalPages = computed(() =>
  userStore.meta ? Math.max(1, Math.ceil(userStore.meta.total / userStore.meta.per_page)) : 1,
)

onMounted(load)

function load(): void {
  userStore.fetchUsers(page.value)
}

function goToPage(next: number): void {
  page.value = next
  load()
}

function isSelf(user: User): boolean {
  return user.id === authStore.user?.id
}

async function onRoleChange(user: User, event: Event): Promise<void> {
  const role = (event.target as HTMLSelectElement).value
  await saveUser(user, { role, status: user.status })
}

async function onStatusChange(user: User, event: Event): Promise<void> {
  const status = (event.target as HTMLSelectElement).value
  await saveUser(user, { role: user.role.slug, status })
}

async function saveUser(user: User, payload: { role: string; status: string }): Promise<void> {
  savingUserId.value = user.id
  rowError.value = null
  try {
    await userStore.updateUser(user.id, payload)
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null }
      rowError.value = body.error ?? t('admin.users.updateError', { name: user.name })
    } else {
      rowError.value = t('admin.users.updateError', { name: user.name })
    }
    load()
  } finally {
    savingUserId.value = null
  }
}

function roleBadgeClass(slug: string): string {
  if (slug === 'admin') return 'bg-blue-50 text-blue-700'
  if (slug === 'moderator') return 'bg-amber-50 text-amber-700'
  return 'bg-slate-100 text-slate-600'
}

function statusBadgeClass(status: string): string {
  if (status === 'active') return 'bg-emerald-50 text-emerald-700'
  if (status === 'suspended') return 'bg-amber-50 text-amber-700'
  return 'bg-rose-50 text-rose-700'
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-4">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('admin.users.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('admin.users.subtitle') }}</p>
    </div>

    <p v-if="rowError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ rowError }}</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-widest text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.nameHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.emailHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.roleHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.statusHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.lastLoginHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.users.joinedHeader') }}</th>
          </tr>
        </thead>
        <tbody v-if="userStore.loading" class="divide-y divide-slate-100">
          <tr v-for="i in 5" :key="i">
            <td colspan="6" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="userStore.users.length === 0">
          <tr>
            <td colspan="6" class="px-4 py-10 text-center text-xs text-slate-400">{{ t('admin.users.emptyState') }}</td>
          </tr>
        </tbody>
        <tbody v-else class="divide-y divide-slate-100">
          <tr v-for="user in userStore.users" :key="user.id" class="transition-colors duration-150 hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ user.name }}
              <span v-if="isSelf(user)" class="ml-1 text-[10px] font-normal uppercase text-slate-400">{{ t('admin.users.you') }}</span>
            </td>
            <td class="px-4 py-3 text-slate-500">{{ user.email }}</td>
            <td class="px-4 py-3">
              <select
                :value="user.role.slug"
                :disabled="isSelf(user) || savingUserId === user.id"
                class="rounded-full border-0 px-2 py-0.5 text-[11px] font-medium capitalize focus:outline-none focus:ring-2 focus:ring-blue-600/40 disabled:cursor-not-allowed disabled:opacity-60"
                :class="roleBadgeClass(user.role.slug)"
                @change="onRoleChange(user, $event)"
              >
                <option v-for="option in ROLE_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </td>
            <td class="px-4 py-3">
              <select
                :value="user.status"
                :disabled="isSelf(user) || savingUserId === user.id"
                class="rounded-full border-0 px-2 py-0.5 text-[11px] font-medium capitalize focus:outline-none focus:ring-2 focus:ring-blue-600/40 disabled:cursor-not-allowed disabled:opacity-60"
                :class="statusBadgeClass(user.status)"
                @change="onStatusChange(user, $event)"
              >
                <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </td>
            <td class="px-4 py-3 tabular-nums text-slate-500">
              {{ user.last_login_at ? useRelativeTime(user.last_login_at) : t('common.never') }}
            </td>
            <td class="px-4 py-3 tabular-nums text-slate-500">{{ useRelativeTime(user.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="userStore.meta && totalPages > 1" class="flex items-center justify-between">
      <button
        type="button"
        :disabled="page <= 1"
        class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        @click="goToPage(page - 1)"
      >
        {{ t('common.previous') }}
      </button>
      <span class="text-[11px] tabular-nums text-slate-400">Page {{ page }} / {{ totalPages }}</span>
      <button
        type="button"
        :disabled="page >= totalPages"
        class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        @click="goToPage(page + 1)"
      >
        {{ t('common.next') }}
      </button>
    </div>
  </div>
</template>
