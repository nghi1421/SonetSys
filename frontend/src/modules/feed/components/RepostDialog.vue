<script setup lang="ts">
import { ref, watch } from 'vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import SharedPostPreview from './SharedPostPreview.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const props = defineProps<{ open: boolean; post: Post }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

const feedStore = useFeedStore()
const comment = ref('')
const submitting = ref(false)
const error = ref<string | null>(null)

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      comment.value = ''
      error.value = null
    }
  },
)

function close(): void {
  emit('update:open', false)
}

async function onSubmit(): Promise<void> {
  submitting.value = true
  error.value = null

  try {
    await feedStore.createPost({ body: comment.value.trim(), shared_post_id: props.post.id })
    close()
  } catch {
    error.value = 'Could not share this post. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click="close">
    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lg dark:bg-zinc-900" @click.stop>
      <h2 class="text-base font-semibold text-slate-900 dark:text-zinc-100">Share to your profile</h2>

      <textarea
        v-model="comment"
        rows="3"
        placeholder="Say something about this (optional)…"
        class="mt-3 block w-full resize-none rounded-md border border-zinc-200 px-3 py-2 text-sm text-slate-900 transition-colors duration-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-accent-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
      />

      <SharedPostPreview :post="post" class="mt-3" />

      <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

      <div class="mt-4 flex justify-end gap-2">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-sm text-slate-600 transition-colors duration-200 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
          @click="close"
        >
          Cancel
        </button>
        <AppButton label="Share" :loading="submitting" @click="onSubmit" />
      </div>
    </div>
  </div>
</template>
