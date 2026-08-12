<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import AppTextarea from '@/shared/components/ui/AppTextarea.vue'
import SharedPostPreview from './SharedPostPreview.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const props = defineProps<{ open: boolean; post: Post }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

const feedStore = useFeedStore()
const { t } = useI18n()
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
    error.value = t('feed.repostDialog.error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppModal :open="open" size="md" @close="close">
 <h2 class="text-xs font-bold text-cyber-text">
      {{ t('feed.repostDialog.title') }}
    </h2>

    <AppTextarea
      v-model="comment"
      class="mt-3"
      :rows="3"
      :placeholder="t('feed.repostDialog.placeholder')"
    />

    <SharedPostPreview :post="post" class="mt-3" />

 <p v-if="error" class="mt-2 text-xs text-cyber-neon-pink">{{ error }}</p>

    <div class="mt-4 flex justify-end gap-3">
      <button
        type="button"
 class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
        @click="close"
      >
        {{ t('common.cancel') }}
      </button>
      <AppButton :label="t('feed.repostDialog.submit')" :loading="submitting" @click="onSubmit" />
    </div>
  </AppModal>
</template>
