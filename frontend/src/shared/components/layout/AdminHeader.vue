<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ArrowLeft, Menu } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'

const emit = defineEmits<{ 'toggle-sidebar': [] }>()

const router = useRouter()
const authStore = useAuthStore()

async function onLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between px-4 py-3">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="rounded-lg border border-slate-200 p-2 text-slate-500 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/50 focus:ring-offset-2 sm:hidden"
          aria-label="Toggle menu"
          @click="emit('toggle-sidebar')"
        >
          <Menu class="h-4 w-4" />
        </button>
        <div>
          <h1 class="text-sm font-bold tracking-wide text-slate-900">Sonetsys Admin</h1>
          <p class="text-[11px] font-medium uppercase tracking-widest text-slate-400">Control Panel</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <RouterLink
          :to="{ name: 'dashboard' }"
          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/50 focus:ring-offset-2"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          <span class="hidden sm:inline">Back to app</span>
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600/50 focus:ring-offset-2"
          @click="onLogout"
        >
          Log out
        </button>
      </div>
    </div>
  </header>
</template>
