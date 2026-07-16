<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useBillingStore } from '../store/billingStore'
import type { AdminTenant } from '../types'

const billingStore = useBillingStore()

const error = ref<string | null>(null)
const pendingSuspend = ref<AdminTenant | null>(null)
const actingTenantId = ref<number | null>(null)

onMounted(() => {
  billingStore.fetchTenants()
})

function statusDotClass(status: string): string {
  if (status === 'active') return 'bg-cyber-neon-cyan shadow-cyan-glow'
  if (status === 'trial') return 'bg-amber-400'
  return 'bg-cyber-neon-pink shadow-pink-glow'
}

function statusPillClass(status: string): string {
  if (status === 'active') return 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan'
  if (status === 'trial') return 'border-amber-500/30 bg-amber-500/10 text-amber-400'
  return 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
}

async function withErrorHandling(action: () => Promise<void>): Promise<void> {
  error.value = null
  try {
    await action()
  } catch {
    error.value = 'Something went wrong. Please try again.'
  }
}

async function onConfirmSuspend(): Promise<void> {
  if (!pendingSuspend.value) return
  actingTenantId.value = pendingSuspend.value.tenant.id
  await withErrorHandling(() => billingStore.suspendTenant(pendingSuspend.value!.tenant.id))
  actingTenantId.value = null
  pendingSuspend.value = null
}

async function onReactivate(entry: AdminTenant): Promise<void> {
  actingTenantId.value = entry.tenant.id
  await withErrorHandling(() => billingStore.reactivateTenant(entry.tenant.id))
  actingTenantId.value = null
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-4xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Manage Tenants</h1>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <div v-if="billingStore.loadingTenants" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-14 animate-pulse rounded-hud bg-cyber-surface/60" />
        </div>

        <AppAlert v-else-if="billingStore.tenants.length === 0" variant="warning">
          No tenants registered yet.
        </AppAlert>

        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse font-mono text-xs">
            <thead>
              <tr class="border-b border-cyber-border text-left text-[9px] uppercase tracking-widest text-cyber-muted">
                <th class="pb-2 pr-3">Tenant</th>
                <th class="pb-2 pr-3">Status</th>
                <th class="pb-2 pr-3">Plan</th>
                <th class="pb-2 pr-3 text-right">Expires</th>
                <th class="pb-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-cyber-border">
              <tr
                v-for="entry in billingStore.tenants"
                :key="entry.tenant.id"
                class="transition-all duration-300 hover:shadow-cyan-glow"
              >
                <td class="py-3 pr-3">
                  <p class="font-bold text-cyber-text">{{ entry.tenant.name }}</p>
                  <p class="text-[9px] text-cyber-muted">/{{ entry.tenant.slug }}</p>
                </td>
                <td class="py-3 pr-3">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] uppercase tracking-widest"
                    :class="statusPillClass(entry.tenant.status)"
                  >
                    <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(entry.tenant.status)" />
                    {{ entry.tenant.status }}
                  </span>
                </td>
                <td class="py-3 pr-3 text-cyber-muted">
                  {{ entry.subscription?.plan.name ?? '—' }}
                </td>
                <td class="py-3 pr-3 text-right tabular-nums text-cyber-muted">
                  {{
                    entry.subscription?.expires_at
                      ? new Date(entry.subscription.expires_at).toLocaleDateString()
                      : 'Never'
                  }}
                </td>
                <td class="py-3 text-right">
                  <button
                    v-if="entry.tenant.status === 'suspended'"
                    type="button"
                    class="rounded-full border border-cyber-border bg-cyber-glass px-3 py-1 text-[9px] uppercase tracking-widest text-cyber-neon-cyan backdrop-blur-md transition-all duration-300 hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="actingTenantId === entry.tenant.id"
                    @click="onReactivate(entry)"
                  >
                    Reactivate
                  </button>
                  <button
                    v-else
                    type="button"
                    class="rounded-full border border-cyber-border bg-cyber-glass px-3 py-1 text-[9px] uppercase tracking-widest text-cyber-neon-pink backdrop-blur-md transition-all duration-300 hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="actingTenantId === entry.tenant.id"
                    @click="pendingSuspend = entry"
                  >
                    Suspend
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <ConfirmDialog
        :open="!!pendingSuspend"
        title="Suspend this tenant?"
        message="Their members will be blocked from every product feature until they switch to the Free plan or a super admin reactivates them."
        @confirm="onConfirmSuspend"
        @cancel="pendingSuspend = null"
      />
    </div>
  </AppShell>
</template>
