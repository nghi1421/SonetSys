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
    class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-3 py-1.5 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/60 focus:ring-offset-2 focus:ring-offset-cyber-bg"
    :aria-label="t('wallet.balancePill.ariaLabel')"
    @click="goToWallet"
  >
    <Coins class="h-3.5 w-3.5 text-cyber-neon-cyan" />
    <span class="font-mono text-xs font-bold tabular-nums text-cyber-text">{{ walletStore.balance }}</span>
  </button>
</template>
