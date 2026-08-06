<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { Coins, Crown } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useWalletStore } from '@/modules/wallet/store/walletStore'
import { useSubscriptionStore } from '../store/subscriptionStore'
import type { SubscriptionPlanId } from '../types'

const walletStore = useWalletStore()
const subscriptionStore = useSubscriptionStore()
const { t } = useI18n()

const loadError = ref<string | null>(null)
const formError = ref<string | null>(null)
const cancelError = ref<string | null>(null)
const subscribingPlan = ref<SubscriptionPlanId | null>(null)
const cancelling = ref(false)

const isOnFreePlan = computed(() => !subscriptionStore.current || subscriptionStore.current.plan === 'free')
const pickablePlans = computed(() => subscriptionStore.plans.filter((plan) => plan.id !== 'free'))

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await Promise.all([
      walletStore.balance ? Promise.resolve() : walletStore.fetchWallet(),
      subscriptionStore.fetchPlans(),
      subscriptionStore.fetchCurrent(),
    ])
  } catch {
    loadError.value = t('subscription.subscriptionView.loadError')
  }
}

async function onSubscribe(planId: SubscriptionPlanId): Promise<void> {
  formError.value = null
  subscribingPlan.value = planId
  try {
    await subscriptionStore.subscribe(planId)
    await walletStore.fetchWallet()
  } catch (err) {
    formError.value = extractError(err, t('subscription.subscriptionView.form.subscribeError'))
  } finally {
    subscribingPlan.value = null
  }
}

async function onCancel(): Promise<void> {
  cancelError.value = null
  cancelling.value = true
  try {
    await subscriptionStore.cancel()
  } catch (err) {
    cancelError.value = extractError(err, t('subscription.subscriptionView.current.cancelError'))
  } finally {
    cancelling.value = false
  }
}

function extractError(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err) && err.response) {
    const body = err.response.data as {
      error: string | null
      meta: { errors?: Record<string, string[]> } | null
    }
    const firstFieldError = Object.values(body.meta?.errors ?? {})[0]?.[0]
    return firstFieldError ?? body.error ?? fallback
  }
  return fallback
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-sm font-bold text-cyber-text">
              {{ t('subscription.subscriptionView.title') }}
            </h1>
            <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('subscription.subscriptionView.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 px-4 py-2">
            <Coins class="h-4 w-4 text-cyber-neon-cyan" />
            <div class="text-right">
              <p class="hidden font-mono text-xs uppercase tracking-widest text-cyber-neon-cyan sm:block">
                {{ t('subscription.subscriptionView.walletHint') }}
              </p>
              <p class="font-mono text-sm font-bold tabular-nums text-cyber-text">{{ walletStore.balance }}</p>
            </div>
          </div>
        </div>
      </header>

      <AppAlert v-if="loadError" variant="error">{{ loadError }}</AppAlert>
      <AppAlert v-if="formError" variant="error">{{ formError }}</AppAlert>

      <section
        v-if="subscriptionStore.current"
        class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md"
      >
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('subscription.subscriptionView.current.title') }}
        </h2>

        <AppAlert v-if="cancelError" variant="error" class="mt-3">{{ cancelError }}</AppAlert>

        <div class="mt-3 flex items-center justify-between">
          <div>
            <p class="flex items-center gap-1.5 font-mono text-xs text-cyber-text">
              <Crown class="h-3.5 w-3.5 text-cyber-neon-indigo" />
              {{ t(`subscription.subscriptionView.plans.${subscriptionStore.current.plan}`) }}
            </p>
            <p class="mt-1 font-mono text-xs uppercase tracking-widest text-cyber-muted">
              {{
                subscriptionStore.current.auto_renew
                  ? t('subscription.subscriptionView.current.renewsOn')
                  : t('subscription.subscriptionView.current.endsOn')
              }}
              · {{ useRelativeTime(subscriptionStore.current.current_period_end) }}
            </p>
          </div>

          <AppButton
            v-if="subscriptionStore.current.auto_renew && subscriptionStore.current.plan !== 'free'"
            :label="t('subscription.subscriptionView.current.cancel')"
            variant="secondary"
            :loading="cancelling"
            @click="onCancel"
          />
        </div>
      </section>

      <section v-if="isOnFreePlan" class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('subscription.subscriptionView.form.title') }}
        </h2>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div
            v-for="plan in pickablePlans"
            :key="plan.id"
            class="rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/50 hover:shadow-cyan-glow"
          >
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
              {{ t(`subscription.subscriptionView.plans.${plan.id}`) }}
            </p>
            <p class="mt-2 font-mono text-lg font-bold tabular-nums text-cyber-neon-cyan">{{ plan.price }}</p>
            <p class="mt-1 font-mono text-xs text-cyber-muted">
              {{ t('subscription.subscriptionView.form.boostWaiver', { percent: plan.boost_fee_waiver_percent }) }}
            </p>
            <AppButton
              class="mt-3 w-full"
              :label="t('subscription.subscriptionView.form.subscribe')"
              :loading="subscribingPlan === plan.id"
              @click="onSubscribe(plan.id)"
            />
          </div>
        </div>
      </section>
    </div>
  </AppShell>
</template>
