<script setup lang="ts">
import { File, X } from '@lucide/vue'
import { formatBytes } from '@/shared/utils/formatBytes'
import type { Media } from '@/modules/storage/types'

defineProps<{ media: Media | null }>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="media"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
      @click="emit('close')"
    >
      <div class="w-full max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg" @click.stop>
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <p class="truncate text-sm font-medium text-slate-900">{{ media.original_name }}</p>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 transition-colors duration-200 hover:text-slate-700"
            aria-label="Close preview"
            @click="emit('close')"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="flex max-h-[70vh] items-center justify-center bg-slate-50 p-4">
          <img
            v-if="media.type === 'image'"
            :src="media.url"
            :alt="media.original_name"
            class="max-h-[65vh] w-auto rounded-lg object-contain"
          />
          <video
            v-else-if="media.type === 'video'"
            :src="media.url"
            controls
            autoplay
            class="max-h-[65vh] w-auto rounded-lg"
          />
          <div v-else class="flex flex-col items-center py-10 text-slate-400">
            <File class="h-10 w-10" />
            <p class="mt-2 text-xs">Preview not available for this file type.</p>
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
          <p class="text-[11px] text-slate-400">
            {{ formatBytes(media.size) }} · uploaded by {{ media.uploaded_by.name ?? 'Unknown' }}
          </p>
          <RouterLink
            v-if="media.mediable_type === 'post' && media.mediable_id"
            :to="{ name: 'post-detail', params: { id: media.mediable_id } }"
            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700"
          >
            View post
          </RouterLink>
        </div>
      </div>
    </div>
  </Teleport>
</template>
