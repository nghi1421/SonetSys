<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Coins, Receipt } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useWalletStore } from '../store/walletStore'
import type { WalletTransaction } from '../types'

const walletStore = useWalletStore()
const { t } = useI18n()

const page = ref(1)
const loadError = ref<string | null>(null)

const totalPages = computed(() =>
  walletStore.meta ? Math.max(1, Math.ceil(walletStore.meta.total / walletStore.meta.per_page)) : 1,
)

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await Promise.all([walletStore.fetchWallet(), walletStore.fetchTransactions(page.value)])
  } catch {
    loadError.value = t('wallet.historyView.loadError')
  }
}

function goToPage(next: number): void {
  page.value = next
  walletStore.fetchTransactions(page.value).catch(() => {
    loadError.value = t('wallet.historyView.loadError')
  })
}

function signedAmount(transaction: WalletTransaction): string {
  return transaction.type === 'credit' ? `+${transaction.amount}` : `-${transaction.amount}`
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-sm font-bold text-cyber-text">{{ t('wallet.historyView.title') }}</h1>
 <p class="mt-1 text-xs text-cyber-muted">{{ t('wallet.historyView.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 px-4 py-2">
            <Coins class="h-4 w-4 text-cyber-neon-cyan" />
            <div class="text-right">
 <p class="text-xs text-cyber-neon-cyan">
                {{ t('wallet.historyView.currentBalance') }}
              </p>
              <p class="font-mono text-sm font-bold tabular-nums text-cyber-text">{{ walletStore.balance }}</p>
            </div>
          </div>
        </div>
      </header>

      <AppAlert v-if="loadError" variant="error">{{ loadError }}</AppAlert>

      <div v-if="walletStore.loading" class="space-y-3">
        <div
          v-for="i in 4"
          :key="i"
          class="h-14 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60"
        />
      </div>

      <div v-else-if="!loadError && walletStore.transactions.length === 0" class="flex flex-col items-center py-16 text-center">
        <Receipt class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('wallet.historyView.emptyTitle') }}</p>
 <p class="mt-1 text-xs text-cyber-muted">{{ t('wallet.historyView.emptyDescription') }}</p>
      </div>

      <div v-else-if="!loadError" class="overflow-hidden rounded-hud border border-cyber-border bg-cyber-glass backdrop-blur-md">
        <ul class="divide-y divide-cyber-border">
          <li
            v-for="transaction in walletStore.transactions"
            :key="transaction.id"
            class="flex items-center justify-between gap-3 px-4 py-3 transition-all duration-300 hover:shadow-cyan-glow"
          >
            <div class="min-w-0">
 <p class="text-xs font-bold text-cyber-text">
                {{ t(`wallet.reasons.${transaction.reason}`) }}
              </p>
 <p class="mt-0.5 text-xs text-cyber-muted">
                {{ t(`wallet.types.${transaction.type}`) }} · {{ useRelativeTime(transaction.created_at) }}
              </p>
            </div>
            <div class="shrink-0 text-right">
              <p
                class="font-mono text-sm font-bold tabular-nums"
                :class="transaction.type === 'credit' ? 'text-cyber-neon-cyan' : 'text-cyber-neon-pink'"
              >
                {{ signedAmount(transaction) }}
              </p>
              <p class="font-mono text-xs tabular-nums text-cyber-muted">{{ transaction.balance_after }}</p>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="!loadError && walletStore.meta && totalPages > 1" class="flex items-center justify-between">
        <AppButton
          :label="t('wallet.historyView.previous')"
          variant="secondary"
          :disabled="page <= 1"
          @click="goToPage(page - 1)"
        />
        <span class="font-mono text-[11px] tabular-nums text-cyber-muted">{{ page }} / {{ totalPages }}</span>
        <AppButton
          :label="t('wallet.historyView.next')"
          variant="secondary"
          :disabled="page >= totalPages"
          @click="goToPage(page + 1)"
        />
      </div>
    </div>
  </AppShell>
</template>
