<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { Coins, Megaphone } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import { useWalletStore } from '@/modules/wallet/store/walletStore'
import { useAdStore } from '../store/adStore'
import type { AdCampaign, AdCampaignStatus } from '../types'

const walletStore = useWalletStore()
const adStore = useAdStore()
const { t } = useI18n()

const loadError = ref<string | null>(null)
const formError = ref<string | null>(null)
const submitting = ref(false)
const cancellingId = ref<number | null>(null)
const cancelError = ref<string | null>(null)

const form = reactive({
  postId: '',
  budget: '',
  days: '7',
})

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await Promise.all([
      walletStore.balance ? Promise.resolve() : walletStore.fetchWallet(),
      adStore.fetchEligiblePosts(),
      adStore.fetchCampaigns(),
    ])
  } catch {
    loadError.value = t('advertising.advertiseView.loadError')
  }
}

const statusStyles: Record<AdCampaignStatus, string> = {
  pending: 'border-amber-400/30 bg-amber-400/10 text-amber-400',
  approved: 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan',
  rejected: 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink',
  cancelled: 'border-cyber-muted/30 bg-cyber-muted/10 text-cyber-muted',
  completed: 'border-cyber-muted/30 bg-cyber-muted/10 text-cyber-muted',
}

function statusClass(status: AdCampaignStatus): string {
  return statusStyles[status]
}

function postExcerpt(campaign: AdCampaign): string {
  const body = campaign.post?.body ?? ''
  return body.length > 80 ? `${body.slice(0, 80)}…` : body
}

async function submitBoost(): Promise<void> {
  formError.value = null

  const postId = Number(form.postId)
  const budget = Number(form.budget)
  const days = Number(form.days)

  if (!postId || !budget || !days) {
    formError.value = t('advertising.advertiseView.form.submitError')
    return
  }

  submitting.value = true
  try {
    await adStore.submitBoost(postId, budget, days)
    await walletStore.fetchWallet()
    form.postId = ''
    form.budget = ''
    form.days = '7'
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null }
      formError.value = body.error ?? t('advertising.advertiseView.form.submitError')
    } else {
      formError.value = t('advertising.advertiseView.form.submitError')
    }
  } finally {
    submitting.value = false
  }
}

async function onCancel(campaign: AdCampaign): Promise<void> {
  cancelError.value = null
  cancellingId.value = campaign.id
  try {
    await adStore.cancelCampaign(campaign.id)
    await walletStore.fetchWallet()
  } catch {
    cancelError.value = t('advertising.advertiseView.myCampaigns.cancelError')
  } finally {
    cancellingId.value = null
  }
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-sm font-bold text-cyber-text">
              {{ t('advertising.advertiseView.title') }}
            </h1>
            <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('advertising.advertiseView.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 px-4 py-2">
            <Coins class="h-4 w-4 text-cyber-neon-cyan" />
            <div class="text-right">
              <p class="font-mono text-[9px] uppercase tracking-widest text-cyber-neon-cyan">
                {{ t('advertising.advertiseView.walletHint') }}
              </p>
              <p class="font-mono text-sm font-bold tabular-nums text-cyber-text">{{ walletStore.balance }}</p>
            </div>
          </div>
        </div>
      </header>

      <AppAlert v-if="loadError" variant="error">{{ loadError }}</AppAlert>

      <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('advertising.advertiseView.form.title') }}
        </h2>

        <p
          v-if="!adStore.loading && adStore.eligiblePosts.length === 0"
          class="mt-3 font-mono text-xs text-cyber-muted"
        >
          {{ t('advertising.advertiseView.form.noEligiblePosts') }}
        </p>

        <form v-else class="mt-4 space-y-3" @submit.prevent="submitBoost">
          <AppAlert v-if="formError" variant="error">{{ formError }}</AppAlert>

          <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
              {{ t('advertising.advertiseView.form.postLabel') }}
            </label>
            <select
              v-model="form.postId"
              required
              class="mt-1 w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
            >
              <option value="" disabled>{{ t('advertising.advertiseView.form.postPlaceholder') }}</option>
              <option v-for="post in adStore.eligiblePosts" :key="post.id" :value="post.id">
                {{ post.body.slice(0, 60) }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ t('advertising.advertiseView.form.budgetLabel') }}
              </label>
              <input
                v-model="form.budget"
                type="number"
                min="1"
                :max="walletStore.balance || undefined"
                step="1"
                required
                class="mt-1 w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs tabular-nums text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
            </div>
            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ t('advertising.advertiseView.form.daysLabel') }}
              </label>
              <input
                v-model="form.days"
                type="number"
                min="1"
                max="30"
                step="1"
                required
                class="mt-1 w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs tabular-nums text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <AppButton
              type="submit"
              :label="t('advertising.advertiseView.form.submit')"
              :loading="submitting"
            />
          </div>
        </form>
      </section>

      <section class="space-y-3">
        <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
          {{ t('advertising.advertiseView.myCampaigns.title') }}
        </h2>

        <AppAlert v-if="cancelError" variant="error">{{ cancelError }}</AppAlert>

        <div v-if="adStore.loading" class="space-y-3">
          <div
            v-for="i in 3"
            :key="i"
            class="h-16 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60"
          />
        </div>

        <div
          v-else-if="adStore.campaigns.length === 0"
          class="flex flex-col items-center rounded-hud border border-cyber-border bg-cyber-glass py-16 text-center backdrop-blur-md"
        >
          <Megaphone class="h-8 w-8 text-cyber-muted" />
          <p class="mt-4 text-xs font-bold text-cyber-text">
            {{ t('advertising.advertiseView.myCampaigns.emptyTitle') }}
          </p>
          <p class="mt-1 font-mono text-xs text-cyber-muted">
            {{ t('advertising.advertiseView.myCampaigns.emptyDescription') }}
          </p>
        </div>

        <div v-else class="overflow-hidden rounded-hud border border-cyber-border bg-cyber-glass backdrop-blur-md">
          <ul class="divide-y divide-cyber-border">
            <li v-for="campaign in adStore.campaigns" :key="campaign.id" class="p-4 transition-all duration-300 hover:shadow-cyan-glow">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate font-mono text-xs text-cyber-text/90">{{ postExcerpt(campaign) }}</p>
                  <p class="mt-1 font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                    {{ t('advertising.advertiseView.myCampaigns.budget') }}: {{ campaign.budget }} ·
                    {{ useRelativeTime(campaign.created_at) }}
                  </p>
                </div>
                <span
                  class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest"
                  :class="statusClass(campaign.status)"
                >
                  {{ t(`advertising.advertiseView.statuses.${campaign.status}`) }}
                </span>
              </div>

              <div v-if="campaign.status === 'pending'" class="mt-3 flex justify-end">
                <AppButton
                  :label="t('advertising.advertiseView.myCampaigns.cancel')"
                  variant="secondary"
                  :loading="cancellingId === campaign.id"
                  @click="onCancel(campaign)"
                />
              </div>
            </li>
          </ul>
        </div>
      </section>
    </div>
  </AppShell>
</template>
