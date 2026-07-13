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

const name = ref('')
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
    await authStore.register({ name: name.value, email: email.value, password: password.value })
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
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-zinc-100">
          Create your account
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">
          Join your community on Sonetsys
        </p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <AppInput v-model="name" label="Name" autocomplete="name" :error="fieldErrors.name?.[0]" />
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
          autocomplete="new-password"
          :error="fieldErrors.password?.[0]"
        />

        <AppButton type="submit" label="Create account" :loading="loading" class="w-full" />
      </form>

      <p class="text-center text-sm text-slate-500 dark:text-zinc-400">
        Already have an account?
        <RouterLink
          :to="{ name: 'login' }"
          class="font-medium text-accent-600 hover:text-accent-700"
        >
          Sign in
        </RouterLink>
      </p>
    </div>
  </div>
</template>
