<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useStickerStore } from '../store/stickerStore'
import type { Post } from '../types'

const props = defineProps<{ post: Post }>()

const stickerStore = useStickerStore()

const stickerEmoji = computed(
  () => stickerStore.stickers.find((sticker) => sticker.key === props.post.sticker_key)?.emoji ?? null,
)

onMounted(() => {
  if (props.post.media_type === 'sticker') {
    stickerStore.fetchStickers()
  }
})
</script>

<template>
  <img
    v-if="post.media_type === 'image' && post.media_url"
    :src="post.media_url"
    alt="Post attachment"
    class="max-h-96 w-full rounded-md border border-zinc-200 object-cover dark:border-zinc-700"
  />
  <video
    v-else-if="post.media_type === 'video' && post.media_url"
    :src="post.media_url"
    controls
    class="max-h-96 w-full rounded-md border border-zinc-200 dark:border-zinc-700"
  />
  <div
    v-else-if="post.media_type === 'sticker'"
    class="flex h-24 w-24 items-center justify-center rounded-md border border-zinc-200 text-5xl dark:border-zinc-700"
  >
    {{ stickerEmoji ?? '❔' }}
  </div>
</template>
