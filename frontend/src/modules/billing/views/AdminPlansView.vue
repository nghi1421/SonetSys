<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Pencil, Trash2 } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useBillingStore } from '../store/billingStore'
import type { Plan, PlanInterval } from '../types'

const billingStore = useBillingStore()

const error = ref<string | null>(null)
const pendingDelete = ref<Plan | null>(null)

const name = ref('')
const slug = ref('')
const priceDollars = ref('0')
const interval = ref<PlanInterval>('month')
const maxUsers = ref('')
const featuresText = ref('')
const saving = ref(false)

const editingPlanId = ref<number | null>(null)
const editName = ref('')
const editPriceDollars = ref('0')
const editInterval = ref<PlanInterval>('month')
const editMaxUsers = ref('')
const editFeaturesText = ref('')
const editIsActive = ref(true)
const savingEdit = ref(false)

onMounted(() => {
  billingStore.fetchAllPlans()
})

function parseFeatures(text: string): string[] {
  return text
    .split(',')
    .map((f) => f.trim())
    .filter((f) => f.length > 0)
}

async function withErrorHandling(action: () => Promise<void>): Promise<void> {
  error.value = null
  try {
    await action()
  } catch {
    error.value = 'Something went wrong. Please try again.'
  }
}

async function onCreate(): Promise<void> {
  if (!name.value.trim() || !slug.value.trim()) return
  saving.value = true
  await withErrorHandling(async () => {
    await billingStore.createPlan({
      name: name.value.trim(),
      slug: slug.value.trim(),
      price_cents: Math.round(Number(priceDollars.value) * 100),
      interval: interval.value,
      max_users: maxUsers.value ? Number(maxUsers.value) : null,
      features: parseFeatures(featuresText.value),
    })
    name.value = ''
    slug.value = ''
    priceDollars.value = '0'
    maxUsers.value = ''
    featuresText.value = ''
  })
  saving.value = false
}

function startEdit(plan: Plan): void {
  editingPlanId.value = plan.id
  editName.value = plan.name
  editPriceDollars.value = String(plan.price_cents / 100)
  editInterval.value = plan.interval
  editMaxUsers.value = plan.max_users ? String(plan.max_users) : ''
  editFeaturesText.value = plan.features.join(', ')
  editIsActive.value = plan.is_active
}

function cancelEdit(): void {
  editingPlanId.value = null
}

async function onSaveEdit(plan: Plan): Promise<void> {
  savingEdit.value = true
  await withErrorHandling(async () => {
    await billingStore.updatePlan(plan.id, {
      name: editName.value.trim(),
      price_cents: Math.round(Number(editPriceDollars.value) * 100),
      interval: editInterval.value,
      max_users: editMaxUsers.value ? Number(editMaxUsers.value) : null,
      features: parseFeatures(editFeaturesText.value),
      is_active: editIsActive.value,
    })
    editingPlanId.value = null
  })
  savingEdit.value = false
}

async function onConfirmDelete(): Promise<void> {
  if (!pendingDelete.value) return
  await withErrorHandling(() => billingStore.deletePlan(pendingDelete.value!.id))
  pendingDelete.value = null
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Manage Pricing Plans</h1>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">Plans</h2>

        <div v-if="billingStore.loadingPlans" class="mt-4 space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 animate-pulse rounded-hud bg-cyber-surface/60" />
        </div>

        <ul v-else class="mt-4 divide-y divide-cyber-border">
          <li v-for="plan in billingStore.plans" :key="plan.id" class="py-3">
            <div v-if="editingPlanId === plan.id" class="space-y-2">
              <AppInput v-model="editName" label="Name" />
              <AppInput v-model="editPriceDollars" label="Price (USD)" type="number" />
              <select
                v-model="editInterval"
                class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              >
                <option value="free">Free</option>
                <option value="month">Monthly</option>
                <option value="year">Yearly</option>
              </select>
              <AppInput v-model="editMaxUsers" label="Max Users (blank = unlimited)" type="number" />
              <AppInput v-model="editFeaturesText" label="Features (comma-separated)" />
              <label class="flex items-center gap-2 font-mono text-xs text-cyber-text">
                <input v-model="editIsActive" type="checkbox" class="rounded border-cyber-border" />
                Visible on pricing page
              </label>
              <div class="flex gap-2">
                <AppButton label="Save" :loading="savingEdit" @click="onSaveEdit(plan)" />
                <AppButton label="Cancel" variant="secondary" @click="cancelEdit" />
              </div>
            </div>

            <div v-else class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-xs font-bold text-cyber-text">
                  {{ plan.name }}
                  <span v-if="!plan.is_active" class="ml-1 font-mono text-[9px] uppercase text-cyber-neon-pink">hidden</span>
                </p>
                <p class="mt-0.5 font-mono text-[9px] uppercase tracking-widest tabular-nums text-cyber-muted">
                  ${{ (plan.price_cents / 100).toFixed(2) }} / {{ plan.interval }} · /{{ plan.slug }}
                </p>
              </div>
              <div class="flex shrink-0 items-center gap-1">
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/50 hover:text-cyber-neon-indigo hover:shadow-cyan-glow"
                  title="Edit"
                  @click="startEdit(plan)"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
                  title="Delete"
                  @click="pendingDelete = plan"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </li>
        </ul>

        <div class="mt-4 space-y-2 border-t border-cyber-border pt-4">
          <AppInput v-model="name" label="New Plan Name" />
          <AppInput v-model="slug" label="Slug" />
          <AppInput v-model="priceDollars" label="Price (USD, 0 = free)" type="number" />
          <select
            v-model="interval"
            class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          >
            <option value="free">Free</option>
            <option value="month">Monthly</option>
            <option value="year">Yearly</option>
          </select>
          <AppInput v-model="maxUsers" label="Max Users (blank = unlimited)" type="number" />
          <AppInput v-model="featuresText" label="Features (comma-separated)" />
          <AppButton
            label="Add Plan"
            :loading="saving"
            :disabled="!name.trim() || !slug.trim()"
            @click="onCreate"
          />
        </div>
      </section>

      <ConfirmDialog
        :open="!!pendingDelete"
        title="Delete this plan?"
        message="Plans with active subscriptions can't be deleted — deactivate it instead so it drops off the pricing page."
        @confirm="onConfirmDelete"
        @cancel="pendingDelete = null"
      />
    </div>
  </AppShell>
</template>
