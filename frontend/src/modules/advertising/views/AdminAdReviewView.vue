<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useAdStore } from '../store/adStore'
import type { AdminAdCampaign } from '../types'

const adStore = useAdStore()
const { t } = useI18n()
const page = ref(1)
const loadError = ref<string | null>(null)
const rowError = ref<string | null>(null)
const approvingId = ref<number | null>(null)

const rejectTarget = ref<AdminAdCampaign | null>(null)
const submitting = ref(false)
const form = reactive({ reason: '' })

const totalPages = computed(() =>
  adStore.adminMeta ? Math.max(1, Math.ceil(adStore.adminMeta.total / adStore.adminMeta.per_page)) : 1,
)

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await adStore.fetchAdminQueue(page.value)
  } catch {
    loadError.value = t('admin.ads.loadError')
  }
}

function goToPage(next: number): void {
  page.value = next
  load()
}

function postExcerpt(campaign: AdminAdCampaign): string {
  const body = campaign.post?.body ?? ''
  return body.length > 60 ? `${body.slice(0, 60)}…` : body
}

async function onApprove(campaign: AdminAdCampaign): Promise<void> {
  rowError.value = null
  approvingId.value = campaign.id
  try {
    await adStore.approveCampaign(campaign.id)
  } catch (err) {
    rowError.value = extractError(err, t('admin.ads.approveError'))
  } finally {
    approvingId.value = null
  }
}

function openReject(campaign: AdminAdCampaign): void {
  rejectTarget.value = campaign
  form.reason = ''
  rowError.value = null
}

function closeReject(): void {
  rejectTarget.value = null
}

async function submitReject(): Promise<void> {
  if (!rejectTarget.value) return

  submitting.value = true
  rowError.value = null
  try {
    await adStore.rejectCampaign(rejectTarget.value.id, form.reason)
    closeReject()
  } catch (err) {
    rowError.value = extractError(err, t('admin.ads.rejectError'))
  } finally {
    submitting.value = false
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
      <h1 class="text-lg font-bold text-slate-900">{{ t('admin.ads.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('admin.ads.subtitle') }}</p>
    </div>

    <p v-if="loadError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ loadError }}</p>
    <p v-if="rowError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ rowError }}</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
 <thead class="border-b border-slate-200 bg-slate-50 text-[11px] text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('admin.ads.advertiserHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.ads.postHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.ads.budgetHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.ads.requestedHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.ads.actionsHeader') }}</th>
          </tr>
        </thead>
        <tbody v-if="adStore.adminLoading" class="divide-y divide-slate-100">
          <tr v-for="i in 5" :key="i">
            <td colspan="5" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="adStore.adminCampaigns.length === 0">
          <tr>
            <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">{{ t('admin.ads.emptyState') }}</td>
          </tr>
        </tbody>
        <tbody v-else class="divide-y divide-slate-100">
          <tr
            v-for="campaign in adStore.adminCampaigns"
            :key="campaign.id"
            class="transition-colors duration-150 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ campaign.advertiser.name }}
              <div class="text-xs text-slate-400">{{ campaign.advertiser.email }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ postExcerpt(campaign) }}</td>
            <td class="px-4 py-3 tabular-nums text-slate-900">{{ campaign.budget }}</td>
            <td class="px-4 py-3 text-slate-500">{{ new Date(campaign.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button
                  type="button"
                  :disabled="approvingId === campaign.id"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-emerald-300 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="onApprove(campaign)"
                >
                  {{ t('admin.ads.approveButton') }}
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-rose-300 hover:text-rose-700"
                  @click="openReject(campaign)"
                >
                  {{ t('admin.ads.rejectButton') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="adStore.adminMeta && totalPages > 1" class="flex items-center justify-between">
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

    <div v-if="rejectTarget" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-900/30 p-4" @click="closeReject">
      <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-5 shadow-lg" @click.stop>
        <h2 class="text-sm font-bold text-slate-900">
          {{ t('admin.ads.rejectTitle', { name: rejectTarget.advertiser.name }) }}
        </h2>

        <form class="mt-4 space-y-3" @submit.prevent="submitReject">
          <div>
            <label class="block text-xs font-medium text-slate-600">{{ t('admin.ads.reasonLabel') }}</label>
            <textarea
              v-model="form.reason"
              rows="3"
              maxlength="500"
              required
              :placeholder="t('admin.ads.reasonPlaceholder')"
              class="mt-1 w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
            />
          </div>
          <div class="flex justify-end gap-2 pt-1">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-300"
              @click="closeReject"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ t('admin.ads.rejectSubmit') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
