<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import type { ValidationErrorMeta } from '@/shared/api/types'
import { useAuthStore } from '../store/authStore'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const email = ref('')
const password = ref('')
const loading = ref(false)
const generalError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

async function onSubmit(): Promise<void> {
  loading.value = true
  generalError.value = null
  fieldErrors.value = {}

  try {
    await authStore.login({ email: email.value, password: password.value })
    router.push({ name: 'dashboard' })
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null; meta: ValidationErrorMeta | null }
      generalError.value = body.error
      fieldErrors.value = body.meta?.errors ?? {}
    } else {
      generalError.value = t('common.somethingWentWrong')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm space-y-6">
      <div class="text-center">
        <h1
          class="bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink bg-clip-text text-sm font-bold uppercase tracking-widest text-transparent"
        >
          {{ t('auth.login.title') }}
        </h1>
        <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('auth.login.subtitle') }}</p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <AppInput
          v-model="email"
          type="email"
          :label="t('auth.login.emailLabel')"
          autocomplete="email"
          :error="fieldErrors.email?.[0]"
        />
        <AppInput
          v-model="password"
          type="password"
          :label="t('auth.login.passwordLabel')"
          autocomplete="current-password"
          :error="fieldErrors.password?.[0]"
        />

        <p class="text-right">
          <RouterLink
            :to="{ name: 'forgot-password' }"
            class="font-mono text-xs text-cyber-muted transition-colors duration-300 hover:text-cyber-neon-cyan"
          >
            {{ t('auth.login.forgotPassword') }}
          </RouterLink>
        </p>

        <AppButton type="submit" :label="t('auth.login.submit')" :loading="loading" class="w-full" />
      </form>

      <p class="text-center font-mono text-xs text-cyber-muted">
        {{ t('auth.login.noAccount') }}
        <RouterLink
          :to="{ name: 'register' }"
          class="font-bold text-cyber-neon-cyan transition-colors duration-300 hover:text-cyber-neon-indigo"
        >
          {{ t('auth.login.registerLink') }}
        </RouterLink>
      </p>
    </div>
  </div>
</template>
