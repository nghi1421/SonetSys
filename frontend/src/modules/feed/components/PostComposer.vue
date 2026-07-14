<script setup lang="ts">
import { ref } from 'vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useFeedStore } from '../store/feedStore'

const feedStore = useFeedStore()
const body = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

async function onSubmit(): Promise<void> {
  if (!body.value.trim()) return

  loading.value = true
  error.value = null

  try {
    await feedStore.createPost({ body: body.value.trim() })
    body.value = ''
  } catch {
    error.value = 'Could not publish your post. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form
    class="space-y-3 rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/30"
    @submit.prevent="onSubmit"
  >
    <textarea
      v-model="body"
      rows="3"
      placeholder="Share something with your community…"
      class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
    />
    <p v-if="error" class="font-mono text-xs text-cyber-neon-pink">{{ error }}</p>
    <div class="flex justify-end">
      <AppButton type="submit" label="Post" :loading="loading" :disabled="!body.trim()" />
    </div>
  </form>
</template>
