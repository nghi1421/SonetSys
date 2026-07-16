<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import type { ValidationErrorMeta } from '@/shared/api/types'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useBillingStore } from '../store/billingStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const billingStore = useBillingStore()

const companyName = ref('')
const companySlug = ref('')
const slugEditedManually = ref(false)
const adminName = ref('')
const adminEmail = ref('')
const adminPassword = ref('')
const generalError = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

const selectedPlan = computed(() =>
  billingStore.publicPlans.find((plan) => plan.slug === route.query.plan) ?? null,
)

onMounted(async () => {
  if (billingStore.publicPlans.length === 0) {
    await billingStore.fetchPublicPlans()
  }
})

watch(companyName, (value) => {
  if (slugEditedManually.value) return
  companySlug.value = value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
})

function onSlugInput(): void {
  slugEditedManually.value = true
}

async function onSubmit(): Promise<void> {
  if (!selectedPlan.value) return

  generalError.value = null
  fieldErrors.value = {}

  try {
    const response = await billingStore.registerTenant({
      company_name: companyName.value.trim(),
      company_slug: companySlug.value.trim(),
      admin_name: adminName.value.trim(),
      admin_email: adminEmail.value.trim(),
      admin_password: adminPassword.value,
      plan_id: selectedPlan.value.id,
    })

    if (response.data) {
      authStore.setSession(response.data.admin, response.data.token)
      router.push({ name: 'dashboard' })
    }
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null; meta: ValidationErrorMeta | null }
      generalError.value = body.error
      fieldErrors.value = body.meta?.errors ?? {}
    } else {
      generalError.value = 'Something went wrong. Please try again.'
    }
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm space-y-6">
      <div class="text-center">
        <h1
          class="bg-gradient-to-r from-cyber-neon-cyan via-cyber-neon-indigo to-cyber-neon-pink bg-clip-text text-sm font-bold uppercase tracking-widest text-transparent"
        >
          Create your workspace
        </h1>
        <p v-if="selectedPlan" class="mt-1 font-mono text-xs text-cyber-muted">
          You picked the <span class="text-cyber-neon-cyan">{{ selectedPlan.name }}</span> plan
        </p>
        <p v-else class="mt-1 font-mono text-xs text-cyber-neon-pink">
          No plan selected —
          <RouterLink :to="{ name: 'pricing' }" class="underline">choose one first</RouterLink>
        </p>
      </div>

      <AppAlert v-if="generalError">{{ generalError }}</AppAlert>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <AppInput v-model="companyName" label="Company Name" :error="fieldErrors.company_name?.[0]" />
        <AppInput
          v-model="companySlug"
          label="Workspace URL Slug"
          :error="fieldErrors.company_slug?.[0]"
          @input="onSlugInput"
        />
        <AppInput v-model="adminName" label="Your Name" autocomplete="name" :error="fieldErrors.admin_name?.[0]" />
        <AppInput
          v-model="adminEmail"
          type="email"
          label="Your Email"
          autocomplete="email"
          :error="fieldErrors.admin_email?.[0]"
        />
        <AppInput
          v-model="adminPassword"
          type="password"
          label="Password"
          autocomplete="new-password"
          :error="fieldErrors.admin_password?.[0]"
        />

        <AppButton
          type="submit"
          label="Create Workspace"
          :loading="billingStore.registering"
          :disabled="!selectedPlan"
          class="w-full"
        />
      </form>
    </div>
  </div>
</template>
