<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import type { ValidationErrorMeta } from '@/shared/api/types'
import { useAuthStore } from '../store/authStore'

const router = useRouter()
const authStore = useAuthStore()

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
          Sign in
        </h1>
        <p class="mt-1 font-mono text-xs text-cyber-muted">Welcome back to Sonetsys</p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <AppInput
          v-model="email"
          type="email"
          label="Email"
          autocomplete="email"
          :error="fieldErrors.email?.[0]"
        />
        <AppInput
          v-model="password"
          type="password"
          label="Password"
          autocomplete="current-password"
          :error="fieldErrors.password?.[0]"
        />

        <p class="text-right">
          <RouterLink
            :to="{ name: 'forgot-password' }"
            class="font-mono text-xs text-cyber-muted transition-colors duration-300 hover:text-cyber-neon-cyan"
          >
            Forgot password?
          </RouterLink>
        </p>

        <AppButton type="submit" label="Sign in" :loading="loading" class="w-full" />
      </form>

      <p class="text-center font-mono text-xs text-cyber-muted">
        Don't have an account?
        <RouterLink
          :to="{ name: 'register' }"
          class="font-bold text-cyber-neon-cyan transition-colors duration-300 hover:text-cyber-neon-indigo"
        >
          Register
        </RouterLink>
      </p>
      <p class="text-center font-mono text-xs text-cyber-muted">
        Starting a new company?
        <RouterLink
          :to="{ name: 'pricing' }"
          class="font-bold text-cyber-neon-indigo transition-colors duration-300 hover:text-cyber-neon-cyan"
        >
          Create a workspace
        </RouterLink>
      </p>
    </div>
  </div>
</template>
