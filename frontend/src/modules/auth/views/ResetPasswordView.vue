<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import type { ValidationErrorMeta } from '@/shared/api/types'
import { useAuthStore } from '../store/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const token = ref(String(route.query.token ?? ''))
const email = ref(String(route.query.email ?? ''))
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const generalError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

async function onSubmit(): Promise<void> {
  loading.value = true
  generalError.value = null
  fieldErrors.value = {}

  if (password.value !== passwordConfirmation.value) {
    fieldErrors.value = { password: [t('auth.resetPassword.passwordsDoNotMatch')] }
    loading.value = false
    return
  }

  try {
    await authStore.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
    })
    router.push({ name: 'login' })
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
 class="bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink bg-clip-text text-sm font-bold text-transparent"
        >
          {{ t('auth.resetPassword.title') }}
        </h1>
 <p class="mt-1 text-xs text-cyber-muted">{{ t('auth.resetPassword.subtitle') }}</p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <AppInput v-model="email" type="email" :label="t('auth.resetPassword.emailLabel')" autocomplete="email" />
        <AppInput
          v-model="password"
          type="password"
          :label="t('auth.resetPassword.newPasswordLabel')"
          autocomplete="new-password"
          :error="fieldErrors.password?.[0]"
        />
        <AppInput
          v-model="passwordConfirmation"
          type="password"
          :label="t('auth.resetPassword.confirmPasswordLabel')"
          autocomplete="new-password"
        />

        <AppButton type="submit" :label="t('auth.resetPassword.submit')" :loading="loading" class="w-full" />
      </form>

 <p class="text-center text-xs text-cyber-muted">
        {{ t('auth.resetPassword.rememberedPassword') }}
        <RouterLink
          :to="{ name: 'login' }"
          class="font-bold text-cyber-neon-cyan transition-colors duration-300 hover:text-cyber-neon-indigo"
        >
          {{ t('auth.resetPassword.signInLink') }}
        </RouterLink>
      </p>
    </div>
  </div>
</template>
