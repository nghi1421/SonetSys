<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { File, Image as ImageIcon, Play, Trash2 } from '@lucide/vue'
import AdminConfirmDialog from '@/shared/components/admin/AdminConfirmDialog.vue'
import AdminMediaPreviewDialog from '@/shared/components/admin/AdminMediaPreviewDialog.vue'
import { formatBytes } from '@/shared/utils/formatBytes'
import { useStorageStore } from '../store/storageStore'
import type { Media, MediaKind } from '../types'

const storageStore = useStorageStore()

const activeType = ref<MediaKind | 'all'>('all')
const page = ref(1)
const error = ref<string | null>(null)
const pendingDelete = ref<Media | null>(null)
const previewItem = ref<Media | null>(null)
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
  <div class="mx-auto max-w-4xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">Media Library</h1>
      <p class="mt-1 text-sm text-slate-500">Files uploaded across the app.</p>
    </div>

    <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ error }}</p>

    <nav class="flex gap-1 border-b border-slate-200">
      <button
        v-for="filter in filters"
        :key="filter.value"
        type="button"
        class="rounded-t-lg px-4 py-2 text-xs font-medium uppercase tracking-widest transition-colors duration-200"
        :class="
          activeType === filter.value
            ? 'border-b-2 border-blue-600 text-blue-700'
            : 'text-slate-400 hover:text-slate-700'
        "
        @click="onFilterChange(filter.value)"
      >
        {{ filter.label }}
      </button>
    </nav>

    <div v-if="storageStore.loadingMedia" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
      <div v-for="i in 8" :key="i" class="aspect-square animate-pulse rounded-xl bg-slate-100" />
    </div>

    <div v-else-if="storageStore.media.length === 0" class="flex flex-col items-center py-16 text-center">
      <ImageIcon class="h-8 w-8 text-slate-300" />
      <p class="mt-4 text-xs font-bold text-slate-900">No media yet</p>
      <p class="mt-1 text-xs text-slate-400">Files uploaded across the app will show up here.</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        <div
          v-for="item in storageStore.media"
          :key="item.id"
          class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-colors duration-200 hover:border-blue-300"
        >
          <button type="button" class="block w-full text-left" @click="previewItem = item">
            <div class="relative flex aspect-square items-center justify-center bg-slate-50">
              <img
                v-if="item.type === 'image'"
                :src="item.url"
                :alt="item.original_name"
                class="h-full w-full object-cover"
              />
              <template v-else-if="item.type === 'video'">
                <video :src="item.url" muted preload="metadata" class="h-full w-full object-cover" />
                <div class="absolute inset-0 flex items-center justify-center bg-slate-900/10">
                  <Play class="h-6 w-6 fill-white text-white drop-shadow" />
                </div>
              </template>
              <File v-else class="h-8 w-8 text-slate-300" />
            </div>

            <div class="p-2">
              <p class="truncate text-[11px] text-slate-700">{{ item.original_name }}</p>
              <p class="mt-0.5 text-[10px] tabular-nums text-slate-400">{{ formatBytes(item.size) }}</p>
            </div>
          </button>

          <button
            type="button"
            class="absolute right-1.5 top-1.5 rounded-full border border-slate-200 bg-white p-1.5 text-slate-400 opacity-0 shadow-sm transition-all duration-200 hover:border-rose-300 hover:text-rose-600 group-hover:opacity-100"
            title="Delete"
            @click.stop="pendingDelete = item"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>

      <div v-if="storageStore.mediaMeta && storageStore.mediaMeta.last_page > 1" class="flex items-center justify-between pt-2">
        <button
          type="button"
          :disabled="page <= 1"
          class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
          @click="goToPage(page - 1)"
        >
          Previous
        </button>
        <span class="text-[11px] tabular-nums text-slate-400">
          Page {{ storageStore.mediaMeta.current_page }} / {{ storageStore.mediaMeta.last_page }}
        </span>
        <button
          type="button"
          :disabled="!hasNextPage"
          class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
          @click="goToPage(page + 1)"
        >
          Next
        </button>
      </div>
    </template>

    <AdminMediaPreviewDialog :media="previewItem" @close="previewItem = null" />

    <AdminConfirmDialog
      :open="!!pendingDelete"
      title="Delete this file?"
      :message="`This permanently deletes '${pendingDelete?.original_name}'. This can't be undone.`"
      @confirm="onConfirmDelete"
      @cancel="pendingDelete = null"
    />
  </div>
</template>
