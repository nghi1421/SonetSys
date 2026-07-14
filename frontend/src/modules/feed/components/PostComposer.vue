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
    class="space-y-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
    @submit.prevent="onSubmit"
  >
    <textarea
      v-model="body"
      rows="3"
      placeholder="Share something with your community…"
      class="block w-full resize-none rounded-md border border-zinc-200 px-3 py-2 text-sm text-slate-900 transition-colors duration-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
    />
    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    <div class="flex justify-end">
      <AppButton type="submit" label="Post" :loading="loading" :disabled="!body.trim()" />
    </div>
  </form>
</template>
