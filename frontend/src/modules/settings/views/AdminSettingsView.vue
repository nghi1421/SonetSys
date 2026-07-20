<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Database, Server } from '@lucide/vue'
import { useSettingsStore } from '../store/settingsStore'
import type { CacheDriver, RedisClient } from '../types'

const settingsStore = useSettingsStore()
const { t } = useI18n()

const cacheDriver = ref<CacheDriver>('database')
const redisClient = ref<RedisClient>('predis')
const redisHost = ref('')
const redisPort = ref(6379)
const redisPassword = ref('')
const redisDatabase = ref(0)
const mailHost = ref('')
const mailPort = ref(2525)
const mailUsername = ref('')
const mailPassword = ref('')
const mailEncryption = ref('')
const mailFromAddress = ref('')
const mailFromName = ref('')
const maxUploadSizeKb = ref(20480)

const error = ref<string | null>(null)
const saved = ref(false)

onMounted(async () => {
  await settingsStore.fetchSettings()
  applySettingsToForm()
})

watch(() => settingsStore.settings, applySettingsToForm)

function applySettingsToForm(): void {
  const settings = settingsStore.settings
  if (!settings) return

  cacheDriver.value = settings.cache_driver
  redisClient.value = settings.redis_client
  redisHost.value = settings.redis_host
  redisPort.value = settings.redis_port
  redisDatabase.value = settings.redis_database
  redisPassword.value = ''
  mailHost.value = settings.mail_host
  mailPort.value = settings.mail_port
  mailUsername.value = settings.mail_username ?? ''
  mailPassword.value = ''
  mailEncryption.value = settings.mail_encryption ?? ''
  mailFromAddress.value = settings.mail_from_address
  mailFromName.value = settings.mail_from_name
  maxUploadSizeKb.value = settings.max_upload_size_kb
}

async function onSave(): Promise<void> {
  error.value = null
  saved.value = false

  try {
    await settingsStore.saveSettings({
      cache_driver: cacheDriver.value,
      redis_client: redisClient.value,
      redis_host: redisHost.value.trim(),
      redis_port: redisPort.value,
      redis_password: redisPassword.value.trim() ? redisPassword.value.trim() : undefined,
      redis_database: redisDatabase.value,
      mail_host: mailHost.value.trim(),
      mail_port: mailPort.value,
      mail_username: mailUsername.value.trim() ? mailUsername.value.trim() : undefined,
      mail_password: mailPassword.value.trim() ? mailPassword.value.trim() : undefined,
      mail_encryption: mailEncryption.value.trim() ? mailEncryption.value.trim() : undefined,
      mail_from_address: mailFromAddress.value.trim(),
      mail_from_name: mailFromName.value.trim(),
      max_upload_size_kb: maxUploadSizeKb.value,
    })
    redisPassword.value = ''
    mailPassword.value = ''
    saved.value = true
  } catch {
    error.value = t('settings.saveError')
  }
}
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('settings.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('settings.subtitle') }}</p>
    </div>

    <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ error }}</p>
    <p v-if="saved" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700">
      {{ t('settings.saved') }}
    </p>

    <div v-if="settingsStore.loadingSettings" class="h-64 animate-pulse rounded-xl border border-slate-200 bg-slate-100" />

    <form v-else class="space-y-6" @submit.prevent="onSave">
      <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ t('settings.mail.heading') }}</h2>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.hostLabel') }}</label>
            <input
              v-model="mailHost"
              type="text"
              autocomplete="off"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.portLabel') }}</label>
            <input
              v-model.number="mailPort"
              type="number"
              min="1"
              max="65535"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.usernameLabel') }}</label>
          <input
            v-model="mailUsername"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.passwordLabel') }}</label>
          <input
            v-model="mailPassword"
            type="password"
            autocomplete="new-password"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
          <p class="text-[11px] text-slate-400">
            {{ settingsStore.settings?.has_mail_password ? t('settings.mail.passwordHintExisting') : t('settings.mail.passwordHintRequired') }}
          </p>
        </div>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.encryptionLabel') }}</label>
          <input
            v-model="mailEncryption"
            type="text"
            autocomplete="off"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.fromAddressLabel') }}</label>
            <input
              v-model="mailFromAddress"
              type="email"
              autocomplete="off"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.mail.fromNameLabel') }}</label>
            <input
              v-model="mailFromName"
              type="text"
              autocomplete="off"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
        </div>
      </section>

      <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ t('settings.cache.heading') }}</h2>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.cache.driverLabel') }}</label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="flex items-center justify-center gap-2 rounded-lg border px-3 py-3 text-xs font-medium transition-colors duration-200"
              :class="
                cacheDriver === 'database'
                  ? 'border-blue-300 bg-blue-50 text-blue-700'
                  : 'border-slate-200 text-slate-500 hover:border-slate-300'
              "
              @click="cacheDriver = 'database'"
            >
              <Database class="h-3.5 w-3.5" /> {{ t('settings.cache.database') }}
            </button>
            <button
              type="button"
              class="flex items-center justify-center gap-2 rounded-lg border px-3 py-3 text-xs font-medium transition-colors duration-200"
              :class="
                cacheDriver === 'redis'
                  ? 'border-blue-300 bg-blue-50 text-blue-700'
                  : 'border-slate-200 text-slate-500 hover:border-slate-300'
              "
              @click="cacheDriver = 'redis'"
            >
              <Server class="h-3.5 w-3.5" /> {{ t('settings.cache.redis') }}
            </button>
          </div>
          <p class="text-[11px] text-slate-400">{{ t('settings.cache.driverHint') }}</p>
        </div>
      </section>

      <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ t('settings.redis.heading') }}</h2>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.redis.clientLabel') }}</label>
          <select
            v-model="redisClient"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          >
            <option value="predis">predis</option>
            <option value="phpredis">phpredis</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.redis.hostLabel') }}</label>
            <input
              v-model="redisHost"
              type="text"
              autocomplete="off"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.redis.portLabel') }}</label>
            <input
              v-model.number="redisPort"
              type="number"
              min="1"
              max="65535"
              class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
            />
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.redis.passwordLabel') }}</label>
          <input
            v-model="redisPassword"
            type="password"
            autocomplete="new-password"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
          <p class="text-[11px] text-slate-400">
            {{ settingsStore.settings?.has_redis_password ? t('settings.redis.passwordHintExisting') : t('settings.redis.passwordHintRequired') }}
          </p>
        </div>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.redis.databaseLabel') }}</label>
          <input
            v-model.number="redisDatabase"
            type="number"
            min="0"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
        </div>
      </section>

      <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ t('settings.uploads.heading') }}</h2>

        <div class="space-y-1.5">
          <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500">{{ t('settings.uploads.maxSizeLabel') }}</label>
          <input
            v-model.number="maxUploadSizeKb"
            type="number"
            min="1"
            max="512000"
            class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
          />
          <p class="text-[11px] text-slate-400">{{ t('settings.uploads.maxSizeHint') }}</p>
        </div>
      </section>

      <button
        type="submit"
        :disabled="settingsStore.savingSettings"
        class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {{ settingsStore.savingSettings ? t('settings.saving') : t('settings.saveButton') }}
      </button>
    </form>
  </div>
</template>
