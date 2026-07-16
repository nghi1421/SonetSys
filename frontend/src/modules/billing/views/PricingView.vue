<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Check } from '@lucide/vue'
import { useBillingStore } from '../store/billingStore'

const billingStore = useBillingStore()

onMounted(() => {
  billingStore.fetchPublicPlans()
})

function formatPrice(cents: number, interval: string): string {
  if (cents === 0) return 'Free'
  const amount = (cents / 100).toLocaleString(undefined, { style: 'currency', currency: 'USD' })
  return `${amount} / ${interval === 'year' ? 'year' : 'month'}`
}
</script>

<template>
  <div class="min-h-screen bg-cyber-bg px-4 py-16">
    <div class="mx-auto max-w-5xl space-y-10">
      <div class="text-center">
        <h1 class="text-2xl font-bold tracking-wider text-cyber-text">// Pricing</h1>
        <p class="mt-2 font-mono text-xs text-cyber-muted">Choose a plan to spin up your own workspace.</p>
      </div>

      <div v-if="billingStore.loadingPublicPlans" class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div v-for="i in 3" :key="i" class="h-72 animate-pulse rounded-hud bg-cyber-surface/60" />
      </div>

      <div
        v-else-if="billingStore.publicPlans.length === 0"
        class="rounded-hud border border-cyber-border bg-cyber-glass p-8 text-center backdrop-blur-md"
      >
        <p class="font-mono text-xs text-cyber-muted">No plans are available yet. Check back soon.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div
          v-for="plan in billingStore.publicPlans"
          :key="plan.id"
          class="flex flex-col rounded-hud border border-cyber-border bg-cyber-glass p-6 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
        >
          <h2 class="text-sm font-bold tracking-wider text-cyber-text">{{ plan.name }}</h2>
          <p class="mt-2 font-mono text-lg font-bold tabular-nums text-cyber-neon-cyan">
            {{ formatPrice(plan.price_cents, plan.interval) }}
          </p>
          <p v-if="plan.max_users" class="mt-1 font-mono text-[10px] text-cyber-muted">
            Up to {{ plan.max_users }} users
          </p>

          <ul class="mt-4 flex-1 space-y-2">
            <li
              v-for="feature in plan.features"
              :key="feature"
              class="flex items-center gap-2 font-mono text-xs text-cyber-text"
            >
              <Check class="h-3.5 w-3.5 shrink-0 text-cyber-neon-cyan" />
              {{ feature }}
            </li>
          </ul>

          <RouterLink
            :to="{ name: 'register-tenant', query: { plan: plan.slug } }"
            class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink px-4 py-2 font-mono text-xs font-bold uppercase tracking-wider text-white transition-all duration-300 hover:shadow-cyan-glow"
          >
            Get Started
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
