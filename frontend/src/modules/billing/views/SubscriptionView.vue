<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useBillingStore } from '../store/billingStore'

const billingStore = useBillingStore()
const error = ref<string | null>(null)
const switchingPlanId = ref<number | null>(null)

const isSuspended = computed(() => billingStore.subscription?.tenant_status === 'suspended')
const freePlan = computed(() => billingStore.publicPlans.find((plan) => plan.price_cents === 0) ?? null)

onMounted(() => {
  billingStore.fetchSubscription()
  billingStore.fetchPublicPlans()
})

function statusClass(status: string): string {
  if (status === 'active') return 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan'
  if (status === 'trialing') return 'border-amber-500/30 bg-amber-500/10 text-amber-400'
  return 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
}

async function onChangePlan(planId: number): Promise<void> {
  error.value = null
  switchingPlanId.value = planId
  try {
    await billingStore.changePlan(planId)
  } catch {
    error.value = 'Could not switch plan. Please try again.'
  } finally {
    switchingPlanId.value = null
  }
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// My License</h1>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <AppAlert v-if="isSuspended" variant="warning">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <span>Your workspace is suspended. Switch to the Free plan to reactivate it immediately.</span>
          <AppButton
            v-if="freePlan"
            label="Switch to Free to reactivate"
            :loading="switchingPlanId === freePlan.id"
            @click="onChangePlan(freePlan.id)"
          />
        </div>
      </AppAlert>

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

      <section class="space-y-3 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">Change Plan</h2>

        <div v-if="billingStore.loadingPublicPlans" class="space-y-2">
          <div v-for="i in 2" :key="i" class="h-14 animate-pulse rounded-hud bg-cyber-surface/60" />
        </div>

        <ul v-else class="divide-y divide-cyber-border">
          <li
            v-for="plan in billingStore.publicPlans"
            :key="plan.id"
            class="flex items-center justify-between gap-3 py-3"
          >
            <div class="min-w-0">
              <p class="truncate text-xs font-bold text-cyber-text">
                {{ plan.name }}
                <span
                  v-if="billingStore.subscription?.plan.id === plan.id"
                  class="ml-1 font-mono text-[9px] uppercase text-cyber-neon-cyan"
                >current</span>
              </p>
              <p class="mt-0.5 font-mono text-[9px] uppercase tracking-widest tabular-nums text-cyber-muted">
                ${{ (plan.price_cents / 100).toFixed(2) }} / {{ plan.interval }}
              </p>
            </div>
            <AppButton
              label="Switch"
              variant="secondary"
              :disabled="billingStore.subscription?.plan.id === plan.id"
              :loading="switchingPlanId === plan.id"
              @click="onChangePlan(plan.id)"
            />
          </li>
        </ul>
      </section>
    </div>
  </AppShell>
</template>
