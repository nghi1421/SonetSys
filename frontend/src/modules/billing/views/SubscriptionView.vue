<script setup lang="ts">
import { onMounted } from 'vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import { useBillingStore } from '../store/billingStore'

const billingStore = useBillingStore()

onMounted(() => {
  billingStore.fetchSubscription()
})

function statusClass(status: string): string {
  if (status === 'active') return 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan'
  if (status === 'trialing') return 'border-amber-500/30 bg-amber-500/10 text-amber-400'
  return 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// My License</h1>

      <div v-if="billingStore.loadingSubscription" class="h-40 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60" />

      <AppAlert v-else-if="!billingStore.subscription" variant="warning">
        No active subscription found for your workspace.
      </AppAlert>

      <section
        v-else
        class="space-y-4 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">
              {{ billingStore.subscription.plan.name }}
            </h2>
            <p class="mt-1 font-mono text-lg font-bold tabular-nums text-cyber-text">
              ${{ (billingStore.subscription.plan.price_cents / 100).toFixed(2) }}
              <span class="text-xs font-normal text-cyber-muted">/ {{ billingStore.subscription.plan.interval }}</span>
            </p>
          </div>
          <span
            class="inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[9px] uppercase tracking-widest"
            :class="statusClass(billingStore.subscription.status)"
          >
            {{ billingStore.subscription.status }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-4 border-t border-cyber-border pt-4 font-mono text-xs">
          <div>
            <p class="text-[9px] uppercase tracking-widest text-cyber-muted">Started</p>
            <p class="mt-1 tabular-nums text-cyber-text">
              {{ new Date(billingStore.subscription.starts_at).toLocaleDateString() }}
            </p>
          </div>
          <div>
            <p class="text-[9px] uppercase tracking-widest text-cyber-muted">
              {{ billingStore.subscription.status === 'trialing' ? 'Trial ends' : 'Renews / Expires' }}
            </p>
            <p class="mt-1 tabular-nums text-cyber-text">
              {{
                billingStore.subscription.expires_at
                  ? new Date(billingStore.subscription.expires_at).toLocaleDateString()
                  : 'Never'
              }}
            </p>
          </div>
        </div>

        <p v-if="billingStore.subscription.status === 'trialing'" class="font-mono text-[10px] text-cyber-muted">
          This is a trial — no payment has been charged yet.
        </p>
      </section>
    </div>
  </AppShell>
</template>
