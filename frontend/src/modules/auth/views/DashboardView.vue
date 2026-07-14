<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useAuthStore } from '../store/authStore'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  authStore.fetchCurrentUser()
})

async function onLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto max-w-2xl px-4 py-10">
    <header
      class="flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800"
    >
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-zinc-100">
          Sonetsys
        </h1>
        <p class="text-sm text-slate-500 dark:text-zinc-400">Modular Community Framework</p>
      </div>
      <AppButton label="Log out" variant="secondary" @click="onLogout" />
    </header>

    <section v-if="authStore.user" class="mt-6 space-y-1">
      <p class="text-sm text-slate-500 dark:text-zinc-400">Signed in as</p>
      <p class="text-lg font-medium text-slate-900 dark:text-zinc-100">{{ authStore.user.name }}</p>
      <p class="text-sm text-slate-500 dark:text-zinc-400">{{ authStore.user.email }}</p>
      <p class="text-sm text-slate-500 dark:text-zinc-400">Role: {{ authStore.user.role.name }}</p>
    </section>
  </div>
</template>
