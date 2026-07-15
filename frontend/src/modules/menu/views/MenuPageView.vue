<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { FileQuestion, TriangleAlert } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { menuApi } from '../api/menuApi'
import type { MenuItem } from '../types'

const route = useRoute()

const item = ref<MenuItem | null>(null)
const loading = ref(true)
const error = ref(false)

async function load(slug: string): Promise<void> {
  loading.value = true
  error.value = false
  item.value = null

  try {
    const response = await menuApi.show(slug)
    item.value = response.data
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug,
  (slug) => {
    if (typeof slug === 'string') load(slug)
  },
  { immediate: true },
)
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl">
      <div v-if="loading" class="animate-pulse space-y-3">
        <div class="h-4 w-40 rounded-full bg-cyber-border" />
        <div class="h-3 w-full rounded-full bg-cyber-border" />
        <div class="h-3 w-5/6 rounded-full bg-cyber-border" />
      </div>

      <div
        v-else-if="error"
        class="rounded-hud border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 p-5 font-mono text-xs text-cyber-neon-pink"
      >
        <TriangleAlert class="mb-2 h-5 w-5" />
        Could not load this page. Please try again later.
      </div>

      <div v-else-if="item" class="rounded-hud border border-cyber-border bg-cyber-glass p-6 backdrop-blur-md">
        <h1 class="text-sm font-bold tracking-wider text-cyber-text">// {{ item.label }}</h1>

        <div v-if="item.static_page" class="mt-4">
          <h2 class="text-xs font-bold text-cyber-text">{{ item.static_page.title }}</h2>
          <p class="mt-2 whitespace-pre-wrap font-mono text-xs leading-relaxed text-cyber-text/90">
            {{ item.static_page.content }}
          </p>
        </div>

        <div v-else class="mt-8 flex flex-col items-center py-10 text-center">
          <FileQuestion class="h-8 w-8 text-cyber-muted" />
          <p class="mt-4 text-xs font-bold text-cyber-text">No content yet</p>
          <p class="mt-1 font-mono text-xs text-cyber-muted">An admin hasn't attached a page to this section yet.</p>
        </div>
      </div>
    </div>
  </AppShell>
</template>
