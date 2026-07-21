<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Coins } from '@lucide/vue'
import { useWalletStore } from '../store/walletStore'

const walletStore = useWalletStore()
const router = useRouter()
const { t } = useI18n()

onMounted(() => {
  walletStore.fetchWallet()
})

function goToWallet(): void {
  router.push({ name: 'wallet' })
}
</script>

<template>
  <button
    type="button"
    class="inline-flex items-center gap-1.5 rounded-full border-none  "
    :aria-label="t('wallet.balancePill.ariaLabel')"
    @click="goToWallet"
  >
    <Coins class="h-3.5 w-3.5 text-cyber-neon-cyan" />
    <span class="font-mono text-xs font-bold tabular-nums text-cyber-text">{{ walletStore.balance }}</span>
  </button>
</template>
