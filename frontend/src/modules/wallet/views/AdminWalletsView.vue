<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useWalletStore } from '../store/walletStore'
import type { AdminWallet } from '../types'

const walletStore = useWalletStore()
const { t } = useI18n()
const page = ref(1)
const loadError = ref<string | null>(null)
const rowError = ref<string | null>(null)

const topUpTarget = ref<AdminWallet | null>(null)
const submitting = ref(false)
const form = reactive({ amount: '', note: '' })

const totalPages = computed(() =>
  walletStore.adminMeta ? Math.max(1, Math.ceil(walletStore.adminMeta.total / walletStore.adminMeta.per_page)) : 1,
)

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await walletStore.fetchAdminWallets(page.value)
  } catch {
    loadError.value = t('admin.wallets.loadError')
  }
}

function goToPage(next: number): void {
  page.value = next
  load()
}

function openTopUp(wallet: AdminWallet): void {
  topUpTarget.value = wallet
  form.amount = ''
  form.note = ''
  rowError.value = null
}

function closeTopUp(): void {
  topUpTarget.value = null
}

async function submitTopUp(): Promise<void> {
  if (!topUpTarget.value) return

  const amount = Number(form.amount)
  submitting.value = true
  rowError.value = null
  try {
    await walletStore.topUp(topUpTarget.value.user_id, {
      amount,
      note: form.note || undefined,
    })
    closeTopUp()
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null }
      rowError.value = body.error ?? t('admin.wallets.topUpError')
    } else {
      rowError.value = t('admin.wallets.topUpError')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-4">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('admin.wallets.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('admin.wallets.subtitle') }}</p>
    </div>

    <p v-if="loadError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ loadError }}</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
 <thead class="border-b border-slate-200 bg-slate-50 text-[11px] text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('admin.wallets.userHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.wallets.emailHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.wallets.balanceHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.wallets.actionsHeader') }}</th>
          </tr>
        </thead>
        <tbody v-if="walletStore.adminLoading" class="divide-y divide-slate-100">
          <tr v-for="i in 5" :key="i">
            <td colspan="4" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="walletStore.adminWallets.length === 0">
          <tr>
            <td colspan="4" class="px-4 py-10 text-center text-xs text-slate-400">{{ t('admin.wallets.emptyState') }}</td>
          </tr>
        </tbody>
        <tbody v-else class="divide-y divide-slate-100">
          <tr v-for="wallet in walletStore.adminWallets" :key="wallet.user_id" class="transition-colors duration-150 hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-900">{{ wallet.user.name }}</td>
            <td class="px-4 py-3 text-slate-500">{{ wallet.user.email }}</td>
            <td class="px-4 py-3 tabular-nums text-slate-900">{{ wallet.balance }}</td>
            <td class="px-4 py-3">
              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-blue-300 hover:text-blue-700"
                @click="openTopUp(wallet)"
              >
                {{ t('admin.wallets.topUpButton') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="walletStore.adminMeta && totalPages > 1" class="flex items-center justify-between">
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

    <div v-if="topUpTarget" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-900/30 p-4" @click="closeTopUp">
      <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-5 shadow-lg" @click.stop>
        <h2 class="text-sm font-bold text-slate-900">
          {{ t('admin.wallets.topUpTitle', { name: topUpTarget.user.name }) }}
        </h2>

        <p v-if="rowError" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-2 text-xs text-rose-700">{{ rowError }}</p>

        <form class="mt-4 space-y-3" @submit.prevent="submitTopUp">
          <div>
            <label class="block text-xs font-medium text-slate-600">{{ t('admin.wallets.amountLabel') }}</label>
            <input
              v-model="form.amount"
              type="number"
              min="1"
              step="1"
              required
              :placeholder="t('admin.wallets.amountPlaceholder')"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600">{{ t('admin.wallets.noteLabel') }}</label>
            <input
              v-model="form.note"
              type="text"
              maxlength="255"
              :placeholder="t('admin.wallets.notePlaceholder')"
              class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
            />
          </div>
          <div class="flex justify-end gap-2 pt-1">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-300"
              @click="closeTopUp"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ t('admin.wallets.submit') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
