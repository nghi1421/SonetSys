<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Cloud, HardDrive } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import { useStorageStore } from '../store/storageStore'
import type { StorageDriver } from '../types'

const storageStore = useStorageStore()

const driver = ref<StorageDriver>('local')
const bucket = ref('')
const region = ref('')
const key = ref('')
const secret = ref('')
const endpoint = ref('')
const usePathStyleEndpoint = ref(false)
const error = ref<string | null>(null)
const saved = ref(false)

onMounted(async () => {
  await storageStore.fetchSettings()
  applyConfigToForm()
})

watch(() => storageStore.config, applyConfigToForm)

function applyConfigToForm(): void {
  const config = storageStore.config
  if (!config) return
  driver.value = config.driver
  bucket.value = config.bucket ?? ''
  region.value = config.region ?? ''
  key.value = config.key ?? ''
  endpoint.value = config.endpoint ?? ''
  usePathStyleEndpoint.value = config.use_path_style_endpoint
  secret.value = ''
}

async function onSave(): Promise<void> {
  error.value = null
  saved.value = false

  try {
    await storageStore.saveSettings({
      driver: driver.value,
      bucket: driver.value === 's3' ? bucket.value.trim() : undefined,
      region: driver.value === 's3' ? region.value.trim() : undefined,
      key: driver.value === 's3' ? key.value.trim() : undefined,
      secret: secret.value.trim() ? secret.value.trim() : undefined,
      endpoint: endpoint.value.trim() ? endpoint.value.trim() : undefined,
      use_path_style_endpoint: usePathStyleEndpoint.value,
    })
    secret.value = ''
    saved.value = true
  } catch {
    error.value = 'Could not save storage settings. Please check your credentials and try again.'
  }
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Storage Settings</h1>

      <AppAlert v-if="error">{{ error }}</AppAlert>
      <AppAlert v-if="saved" variant="success">Storage settings saved.</AppAlert>

      <div v-if="storageStore.loadingConfig" class="h-64 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60" />

      <form v-else class="space-y-5 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @submit.prevent="onSave">
        <div class="space-y-1.5">
          <label class="block text-[9px] font-mono uppercase tracking-widest text-cyber-neon-cyan">
            Storage Driver
          </label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="flex items-center justify-center gap-2 rounded-hud border px-3 py-3 font-mono text-xs transition-all duration-300"
              :class="
                driver === 'local'
                  ? 'border-cyber-neon-cyan/50 bg-cyber-neon-cyan/10 text-cyber-neon-cyan shadow-cyan-glow'
                  : 'border-cyber-border bg-cyber-surface/60 text-cyber-muted hover:border-cyber-neon-cyan/30'
              "
              @click="driver = 'local'"
            >
              <HardDrive class="h-3.5 w-3.5" /> Local Disk
            </button>
            <button
              type="button"
              class="flex items-center justify-center gap-2 rounded-hud border px-3 py-3 font-mono text-xs transition-all duration-300"
              :class="
                driver === 's3'
                  ? 'border-cyber-neon-indigo/50 bg-cyber-neon-indigo/10 text-cyber-neon-indigo shadow-cyan-glow'
                  : 'border-cyber-border bg-cyber-surface/60 text-cyber-muted hover:border-cyber-neon-indigo/30'
              "
              @click="driver = 's3'"
            >
              <Cloud class="h-3.5 w-3.5" /> Amazon S3
            </button>
          </div>
          <p class="font-mono text-[10px] text-cyber-muted">
            New uploads use this driver. Files already stored elsewhere are not migrated automatically.
          </p>
        </div>

        <template v-if="driver === 's3'">
          <AppInput v-model="bucket" label="Bucket" autocomplete="off" />
          <AppInput v-model="region" label="Region" autocomplete="off" />
          <AppInput v-model="key" label="Access Key ID" autocomplete="off" />
          <AppInput
            v-model="secret"
            label="Secret Access Key"
            type="password"
            autocomplete="new-password"
          />
          <p class="-mt-3 font-mono text-[10px] text-cyber-muted">
            {{ storageStore.config?.has_secret ? 'A secret is already saved — leave blank to keep it.' : 'Required.' }}
          </p>
          <AppInput v-model="endpoint" label="Custom Endpoint (optional)" autocomplete="off" />
          <label class="flex items-center gap-2 font-mono text-xs text-cyber-text">
            <input v-model="usePathStyleEndpoint" type="checkbox" class="rounded border-cyber-border" />
            Use path-style endpoint
          </label>
        </template>

        <AppButton type="submit" label="Save Settings" :loading="storageStore.savingConfig" />
      </form>
    </div>
  </AppShell>
</template>
