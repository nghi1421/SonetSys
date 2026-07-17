<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Cloud, HardDrive } from '@lucide/vue'
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
  <div class="mx-auto max-w-2xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">Storage</h1>
      <p class="mt-1 text-sm text-slate-500">Choose where uploaded files are stored.</p>
    </div>

    <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ error }}</p>
    <p v-if="saved" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700">
      Storage settings saved.
    </p>

    <div v-if="storageStore.loadingConfig" class="h-64 animate-pulse rounded-xl border border-slate-200 bg-slate-100" />

    <form
      v-else
      class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
      @submit.prevent="onSave"
    >
      <div class="space-y-1.5">
        <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">Storage Driver</label>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="flex items-center justify-center gap-2 rounded-lg border px-3 py-3 text-xs font-medium transition-colors duration-200"
            :class="
              driver === 'local'
                ? 'border-blue-300 bg-blue-50 text-blue-700'
                : 'border-slate-200 text-slate-500 hover:border-slate-300'
            "
            @click="driver = 'local'"
          >
            <HardDrive class="h-3.5 w-3.5" /> Local Disk
          </button>
          <button
            type="button"
            class="flex items-center justify-center gap-2 rounded-lg border px-3 py-3 text-xs font-medium transition-colors duration-200"
            :class="
              driver === 's3'
                ? 'border-blue-300 bg-blue-50 text-blue-700'
                : 'border-slate-200 text-slate-500 hover:border-slate-300'
            "
            @click="driver = 's3'"
          >
            <Cloud class="h-3.5 w-3.5" /> Amazon S3
          </button>
        </div>
        <p class="text-[11px] text-slate-400">
          New uploads use this driver. Files already stored elsewhere are not migrated automatically.
        </p>
      </div>

      <template v-if="driver === 's3'">
        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">Bucket</label>
          <input
            v-model="bucket"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">Region</label>
          <input
            v-model="region"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">Access Key ID</label>
          <input
            v-model="key"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">Secret Access Key</label>
          <input
            v-model="secret"
            type="password"
            autocomplete="new-password"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
        <p class="-mt-3 text-[11px] text-slate-400">
          {{ storageStore.config?.has_secret ? 'A secret is already saved — leave blank to keep it.' : 'Required.' }}
        </p>
        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">
            Custom Endpoint (optional)
          </label>
          <input
            v-model="endpoint"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-700">
          <input v-model="usePathStyleEndpoint" type="checkbox" class="rounded border-slate-300" />
          Use path-style endpoint
        </label>
      </template>

      <button
        type="submit"
        :disabled="storageStore.savingConfig"
        class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {{ storageStore.savingConfig ? 'Saving…' : 'Save Settings' }}
      </button>
    </form>
  </div>
</template>
