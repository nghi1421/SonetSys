<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { X } from '@lucide/vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import PostCard from './PostCard.vue'
import type { Post } from '../types'

defineProps<{ post: Post }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
</script>

<template>
  <AppModal :open="true" bare size="lg" @close="emit('close')">
    <template #before>
      <button
        type="button"
        class="fixed right-4 top-4 z-[60] rounded-full border border-cyber-border bg-cyber-glass p-2 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
        :aria-label="t('common.close')"
        @click="emit('close')"
      >
        <X class="h-4 w-4" />
      </button>
    </template>

    <PostCard :post="post" start-with-comments-open />
  </AppModal>
</template>
