<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import type { ValidationErrorMeta } from '@/shared/api/types'
import { useAuthStore } from '../store/authStore'

const authStore = useAuthStore()

const email = ref('')
const loading = ref(false)
const generalError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})
const submitted = ref(false)

async function onSubmit(): Promise<void> {
  loading.value = true
  generalError.value = null
  fieldErrors.value = {}

  try {
    await authStore.forgotPassword({ email: email.value })
    submitted.value = true
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null; meta: ValidationErrorMeta | null }
      generalError.value = body.error
      fieldErrors.value = body.meta?.errors ?? {}
    } else {
      generalError.value = 'Something went wrong. Please try again.'
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
          Forgot Password
        </h1>
        <p class="mt-1 font-mono text-xs text-cyber-muted">We'll email you a reset link</p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <AppAlert v-if="submitted" variant="success">
        If that email is registered, a password reset link has been sent.
      </AppAlert>

      <form v-else class="space-y-4" @submit.prevent="onSubmit">
        <AppInput
          v-model="email"
          type="email"
          label="Email"
          autocomplete="email"
          :error="fieldErrors.email?.[0]"
        />

        <AppButton type="submit" label="Send Reset Link" :loading="loading" class="w-full" />
      </form>

      <p class="text-center font-mono text-xs text-cyber-muted">
        Remembered your password?
        <RouterLink
          :to="{ name: 'login' }"
          class="font-bold text-cyber-neon-cyan transition-colors duration-300 hover:text-cyber-neon-indigo"
        >
          Sign in
        </RouterLink>
      </p>
    </div>
  </div>
</template>
