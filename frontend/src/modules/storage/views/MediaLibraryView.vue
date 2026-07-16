<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { File, FileVideo, Image as ImageIcon, Trash2 } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useStorageStore } from '../store/storageStore'
import type { Media, MediaKind } from '../types'

const storageStore = useStorageStore()

const activeType = ref<MediaKind | 'all'>('all')
const page = ref(1)
const error = ref<string | null>(null)
const pendingDelete = ref<Media | null>(null)
const deleting = ref(false)

const filters: Array<{ value: MediaKind | 'all'; label: string }> = [
  { value: 'all', label: 'All' },
  { value: 'image', label: 'Images' },
  { value: 'video', label: 'Videos' },
  { value: 'file', label: 'Files' },
]

const hasNextPage = computed(
  () => !!storageStore.mediaMeta && storageStore.mediaMeta.current_page < storageStore.mediaMeta.last_page,
)

onMounted(load)

async function load(): Promise<void> {
  error.value = null
  try {
    await storageStore.fetchMedia(page.value, activeType.value === 'all' ? undefined : activeType.value)
  } catch {
    error.value = 'Could not load the media library. Please try again.'
  }
}

function onFilterChange(type: MediaKind | 'all'): void {
  activeType.value = type
  page.value = 1
  load()
}

function goToPage(next: number): void {
  page.value = next
  load()
}

function iconFor(type: MediaKind) {
  if (type === 'image') return ImageIcon
  if (type === 'video') return FileVideo
  return File
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

async function onConfirmDelete(): Promise<void> {
  if (!pendingDelete.value) return
  deleting.value = true
  try {
    await storageStore.deleteMedia(pendingDelete.value.id)
  } catch {
    error.value = 'Could not delete this file. Please try again.'
  } finally {
    deleting.value = false
    pendingDelete.value = null
  }
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-4xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Media Library</h1>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <nav class="flex gap-1 border-b border-cyber-border">
        <button
          v-for="filter in filters"
          :key="filter.value"
          type="button"
          class="rounded-t-hud px-4 py-2 font-mono text-xs uppercase tracking-widest transition-all duration-300"
          :class="
            activeType === filter.value
              ? 'border-b-2 border-cyber-neon-cyan text-cyber-neon-cyan'
              : 'text-cyber-muted hover:text-cyber-text'
          "
          @click="onFilterChange(filter.value)"
        >
          {{ filter.label }}
        </button>
      </nav>

      <div v-if="storageStore.loadingMedia" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        <div v-for="i in 8" :key="i" class="aspect-square animate-pulse rounded-hud bg-cyber-surface/60" />
      </div>

      <div v-else-if="storageStore.media.length === 0" class="flex flex-col items-center py-16 text-center">
        <ImageIcon class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">No media yet</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">Files uploaded across the app will show up here.</p>
      </div>

      <template v-else>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
          <div
            v-for="item in storageStore.media"
            :key="item.id"
            class="group relative overflow-hidden rounded-hud border border-cyber-border bg-cyber-glass backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:shadow-pink-glow"
          >
            <div class="flex aspect-square items-center justify-center bg-cyber-surface/60">
              <img v-if="item.type === 'image'" :src="item.url" :alt="item.original_name" class="h-full w-full object-cover" />
              <component :is="iconFor(item.type)" v-else class="h-8 w-8 text-cyber-muted" />
            </div>

            <div class="p-2">
              <p class="truncate font-mono text-[10px] text-cyber-text">{{ item.original_name }}</p>
              <p class="mt-0.5 font-mono text-[9px] tabular-nums text-cyber-muted">{{ formatSize(item.size) }}</p>
            </div>

            <button
              type="button"
              class="absolute right-1.5 top-1.5 rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted opacity-0 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow group-hover:opacity-100"
              title="Delete"
              @click="pendingDelete = item"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>

        <div v-if="storageStore.mediaMeta && storageStore.mediaMeta.last_page > 1" class="flex items-center justify-between pt-2">
          <button
            type="button"
            :disabled="page <= 1"
            class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
            @click="goToPage(page - 1)"
          >
            Previous
          </button>
          <span class="font-mono text-[10px] tabular-nums text-cyber-muted">
            Page {{ storageStore.mediaMeta.current_page }} / {{ storageStore.mediaMeta.last_page }}
          </span>
          <button
            type="button"
            :disabled="!hasNextPage"
            class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
            @click="goToPage(page + 1)"
          >
            Next
          </button>
        </div>
      </template>

      <ConfirmDialog
        :open="!!pendingDelete"
        title="Delete this file?"
        :message="`This permanently deletes '${pendingDelete?.original_name}'. This can't be undone.`"
        @confirm="onConfirmDelete"
        @cancel="pendingDelete = null"
      />
    </div>
  </AppShell>
</template>
