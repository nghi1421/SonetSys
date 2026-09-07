<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Music, Pencil, Trash2 } from '@lucide/vue'
import AdminConfirmDialog from '@/shared/components/admin/AdminConfirmDialog.vue'
import { useSongStore } from '../store/songStore'
import type { Song } from '../types'

const songStore = useSongStore()
const { t } = useI18n()

const actionError = ref<string | null>(null)

const newTitle = ref('')
const newArtist = ref('')
const newAudioFile = ref<File | null>(null)
const newCoverFile = ref<File | null>(null)
const newAudioInput = ref<HTMLInputElement | null>(null)
const newCoverInput = ref<HTMLInputElement | null>(null)
const creating = ref(false)

const editingId = ref<number | null>(null)
const editTitle = ref('')
const editArtist = ref('')
const editAudioFile = ref<File | null>(null)
const editCoverFile = ref<File | null>(null)
const saving = ref(false)

const pendingDelete = ref<Song | null>(null)
const deleting = ref(false)

onMounted(() => {
  songStore.fetchAdminSongs()
})

function formatDuration(seconds: number | null): string {
  if (seconds === null) return '—'
  const minutes = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

async function withErrorHandling(action: () => Promise<void>): Promise<void> {
  actionError.value = null
  try {
    await action()
  } catch {
    actionError.value = t('songs.admin.genericError')
  }
}

function onNewAudioChange(event: Event): void {
  newAudioFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

function onNewCoverChange(event: Event): void {
  newCoverFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function onCreate(): Promise<void> {
  if (!newTitle.value.trim() || !newAudioFile.value) return
  creating.value = true
  await withErrorHandling(async () => {
    await songStore.createSong({
      title: newTitle.value.trim(),
      artist: newArtist.value.trim() || undefined,
      audio: newAudioFile.value!,
      cover: newCoverFile.value ?? undefined,
    })
    newTitle.value = ''
    newArtist.value = ''
    newAudioFile.value = null
    newCoverFile.value = null
    if (newAudioInput.value) newAudioInput.value.value = ''
    if (newCoverInput.value) newCoverInput.value.value = ''
  })
  creating.value = false
}

function startEdit(song: Song): void {
  editingId.value = song.id
  editTitle.value = song.title
  editArtist.value = song.artist ?? ''
  editAudioFile.value = null
  editCoverFile.value = null
}

function cancelEdit(): void {
  editingId.value = null
}

function onEditAudioChange(event: Event): void {
  editAudioFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

function onEditCoverChange(event: Event): void {
  editCoverFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function onSave(song: Song): Promise<void> {
  if (!editTitle.value.trim()) return
  saving.value = true
  await withErrorHandling(async () => {
    await songStore.updateSong(song.id, {
      title: editTitle.value.trim(),
      artist: editArtist.value.trim() || undefined,
      audio: editAudioFile.value ?? undefined,
      cover: editCoverFile.value ?? undefined,
    })
    editingId.value = null
  })
  saving.value = false
}

async function onConfirmDelete(): Promise<void> {
  if (!pendingDelete.value) return
  deleting.value = true
  await withErrorHandling(async () => {
    await songStore.deleteSong(pendingDelete.value!.id)
  })
  deleting.value = false
  pendingDelete.value = null
}
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('songs.admin.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('songs.admin.subtitle') }}</p>
    </div>

    <p v-if="actionError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
      {{ actionError }}
    </p>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <h2 class="border-b border-slate-200 px-4 py-3 text-xs font-bold text-slate-500">
        {{ t('songs.admin.listTitle') }}
      </h2>

      <div v-if="songStore.loading" class="space-y-2 p-4">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div v-else-if="songStore.songs.length === 0" class="flex flex-col items-center py-10 text-center">
        <Music class="h-6 w-6 text-slate-300" />
        <p class="mt-2 text-xs text-slate-400">{{ t('songs.admin.emptyDescription') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] text-slate-500">
            <tr>
              <th class="px-4 py-2 font-medium">{{ t('songs.admin.songHeader') }}</th>
              <th class="px-4 py-2 font-medium">{{ t('songs.admin.durationHeader') }}</th>
              <th class="px-4 py-2 font-medium">{{ t('songs.admin.actionsHeader') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="song in songStore.songs" :key="song.id">
              <td colspan="3" class="p-0">
                <div v-if="editingId === song.id" class="space-y-2 p-4">
                  <input
                    v-model="editTitle"
                    type="text"
                    maxlength="191"
                    :placeholder="t('songs.admin.titlePlaceholder')"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <input
                    v-model="editArtist"
                    type="text"
                    maxlength="191"
                    :placeholder="t('songs.admin.artistPlaceholder')"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <label class="block text-[11px] text-slate-500">
                    {{ t('songs.admin.replaceAudioLabel') }}
                    <input type="file" accept="audio/*" class="mt-1 block w-full text-xs text-slate-500" @change="onEditAudioChange" />
                  </label>
                  <label class="block text-[11px] text-slate-500">
                    {{ t('songs.admin.replaceCoverLabel') }}
                    <input type="file" accept="image/*" class="mt-1 block w-full text-xs text-slate-500" @change="onEditCoverChange" />
                  </label>
                  <div class="flex gap-2">
                    <button
                      type="button"
                      :disabled="saving"
                      class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                      @click="onSave(song)"
                    >
                      {{ t('common.save') }}
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-300"
                      @click="cancelEdit"
                    >
                      {{ t('common.cancel') }}
                    </button>
                  </div>
                </div>

                <div v-else class="flex items-center justify-between gap-3 px-4 py-3">
                  <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-50">
                      <img v-if="song.cover_url" :src="song.cover_url" :alt="song.title" class="h-full w-full object-cover" />
                      <Music v-else class="h-4 w-4 text-slate-300" />
                    </span>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-slate-900">{{ song.title }}</p>
                      <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ song.artist ?? t('songs.admin.unknownArtist') }}</p>
                    </div>
                  </div>

                  <span class="hidden shrink-0 font-mono text-xs tabular-nums text-slate-500 sm:inline">
                    {{ formatDuration(song.duration_sec) }}
                  </span>

                  <div class="flex shrink-0 items-center gap-1">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-blue-300 hover:text-blue-600"
                      :title="t('common.edit')"
                      @click="startEdit(song)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-rose-300 hover:text-rose-600"
                      :title="t('common.delete')"
                      @click="pendingDelete = song"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-2 border-t border-slate-200 p-4">
        <input
          v-model="newTitle"
          type="text"
          maxlength="191"
          :placeholder="t('songs.admin.titlePlaceholder')"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <input
          v-model="newArtist"
          type="text"
          maxlength="191"
          :placeholder="t('songs.admin.artistPlaceholder')"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <label class="block text-[11px] text-slate-500">
          {{ t('songs.admin.audioLabel') }}
          <input ref="newAudioInput" type="file" accept="audio/*" class="mt-1 block w-full text-xs text-slate-500" @change="onNewAudioChange" />
        </label>
        <label class="block text-[11px] text-slate-500">
          {{ t('songs.admin.coverLabel') }}
          <input ref="newCoverInput" type="file" accept="image/*" class="mt-1 block w-full text-xs text-slate-500" @change="onNewCoverChange" />
        </label>
        <button
          type="button"
          :disabled="creating || !newTitle.trim() || !newAudioFile"
          class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="onCreate"
        >
          {{ t('songs.admin.addButton') }}
        </button>
      </div>
    </section>

    <AdminConfirmDialog
      :open="!!pendingDelete"
      :title="t('songs.admin.confirmDeleteTitle')"
      :message="t('songs.admin.confirmDeleteMessage', { title: pendingDelete?.title })"
      @confirm="onConfirmDelete"
      @cancel="pendingDelete = null"
    />
  </div>
</template>
