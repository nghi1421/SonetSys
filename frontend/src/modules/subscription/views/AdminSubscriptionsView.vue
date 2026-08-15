<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useSubscriptionStore } from '../store/subscriptionStore'
import type { AdminUserSubscription } from '../types'

const subscriptionStore = useSubscriptionStore()
const { t } = useI18n()
const page = ref(1)
const loadError = ref<string | null>(null)
const rowError = ref<string | null>(null)
const cancellingId = ref<number | null>(null)

const totalPages = computed(() =>
  subscriptionStore.adminMeta
    ? Math.max(1, Math.ceil(subscriptionStore.adminMeta.total / subscriptionStore.adminMeta.per_page))
    : 1,
)

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await subscriptionStore.fetchAdminList(page.value)
  } catch {
    loadError.value = t('subscription.adminSubscriptionsView.loadError')
  }
}

function goToPage(next: number): void {
  page.value = next
  load()
}

async function onForceCancel(subscription: AdminUserSubscription): Promise<void> {
  rowError.value = null
  cancellingId.value = subscription.id
  try {
    await subscriptionStore.adminCancel(subscription.id)
  } catch (err) {
    rowError.value = extractError(err, t('subscription.adminSubscriptionsView.cancelError'))
  } finally {
    cancellingId.value = null
  }
}

function extractError(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err) && err.response) {
    const body = err.response.data as { error: string | null }
    return body.error ?? fallback
  }
  return fallback
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-4">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('subscription.adminSubscriptionsView.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('subscription.adminSubscriptionsView.subtitle') }}</p>
    </div>

    <p v-if="loadError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ loadError }}</p>
    <p v-if="rowError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ rowError }}</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
 <thead class="border-b border-slate-200 bg-slate-50 text-[11px] text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('subscription.adminSubscriptionsView.userHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('subscription.adminSubscriptionsView.planHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('subscription.adminSubscriptionsView.statusHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('subscription.adminSubscriptionsView.autoRenewHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('subscription.adminSubscriptionsView.actionsHeader') }}</th>
          </tr>
        </thead>
        <tbody v-if="subscriptionStore.adminLoading" class="divide-y divide-slate-100">
          <tr v-for="i in 5" :key="i">
            <td colspan="5" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="subscriptionStore.adminSubscriptions.length === 0">
          <tr>
            <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">
              {{ t('subscription.adminSubscriptionsView.emptyState') }}
            </td>
          </tr>
        </tbody>
        <tbody v-else class="divide-y divide-slate-100">
          <tr
            v-for="subscription in subscriptionStore.adminSubscriptions"
            :key="subscription.id"
            class="transition-colors duration-150 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ subscription.user.name }}
              <div class="text-xs text-slate-400">{{ subscription.user.email }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ subscription.plan }}</td>
            <td class="px-4 py-3 text-slate-600">{{ subscription.status }}</td>
            <td class="px-4 py-3 text-slate-600">{{ subscription.auto_renew ? 'Yes' : 'No' }}</td>
            <td class="px-4 py-3">
              <button
                type="button"
                :disabled="!subscription.auto_renew || cancellingId === subscription.id"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-rose-300 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                @click="onForceCancel(subscription)"
              >
                {{ t('subscription.adminSubscriptionsView.forceCancel') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="subscriptionStore.adminMeta && totalPages > 1" class="flex items-center justify-between">
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
