<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Pencil, Smile, Trash2 } from '@lucide/vue'
import AdminConfirmDialog from '@/shared/components/admin/AdminConfirmDialog.vue'
import { useReactionTypeStore } from '../store/reactionTypeStore'
import type { ReactionTypeDef } from '../types'

const reactionTypeStore = useReactionTypeStore()
const { t } = useI18n()

const actionError = ref<string | null>(null)

const newKey = ref('')
const newLabel = ref('')
const newEmoji = ref('')
const newIconFile = ref<File | null>(null)
const newIconInput = ref<HTMLInputElement | null>(null)
const creating = ref(false)

const editingId = ref<number | null>(null)
const editLabel = ref('')
const editEmoji = ref('')
const editIconFile = ref<File | null>(null)
const saving = ref(false)

const pendingDelete = ref<ReactionTypeDef | null>(null)
const deleting = ref(false)

onMounted(() => {
  reactionTypeStore.fetchAdminTypes()
})

async function withErrorHandling(action: () => Promise<void>): Promise<void> {
  actionError.value = null
  try {
    await action()
  } catch {
    actionError.value = t('reactions.admin.genericError')
  }
}

function onNewIconChange(event: Event): void {
  newIconFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function onCreate(): Promise<void> {
  if (!newKey.value.trim() || !newLabel.value.trim()) return
  creating.value = true
  await withErrorHandling(async () => {
    await reactionTypeStore.createType({
      key: newKey.value.trim(),
      label: newLabel.value.trim(),
      emoji: newEmoji.value.trim() || undefined,
      icon: newIconFile.value ?? undefined,
    })
    newKey.value = ''
    newLabel.value = ''
    newEmoji.value = ''
    newIconFile.value = null
    if (newIconInput.value) newIconInput.value.value = ''
  })
  creating.value = false
}

function startEdit(reactionType: ReactionTypeDef): void {
  editingId.value = reactionType.id
  editLabel.value = reactionType.label
  editEmoji.value = reactionType.emoji ?? ''
  editIconFile.value = null
}

function cancelEdit(): void {
  editingId.value = null
}

function onEditIconChange(event: Event): void {
  editIconFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function onSave(reactionType: ReactionTypeDef): Promise<void> {
  if (!editLabel.value.trim()) return
  saving.value = true
  await withErrorHandling(async () => {
    await reactionTypeStore.updateType(reactionType.id, {
      label: editLabel.value.trim(),
      emoji: editEmoji.value.trim() || undefined,
      icon: editIconFile.value ?? undefined,
    })
    editingId.value = null
  })
  saving.value = false
}

async function onConfirmDelete(): Promise<void> {
  if (!pendingDelete.value) return
  deleting.value = true
  await withErrorHandling(async () => {
    await reactionTypeStore.deleteType(pendingDelete.value!.id)
  })
  deleting.value = false
  pendingDelete.value = null
}
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('reactions.admin.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('reactions.admin.subtitle') }}</p>
    </div>

    <p v-if="actionError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
      {{ actionError }}
    </p>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
 <h2 class="border-b border-slate-200 px-4 py-3 text-xs font-bold text-slate-500">
        {{ t('reactions.admin.listTitle') }}
      </h2>

      <div v-if="reactionTypeStore.loading" class="space-y-2 p-4">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div v-else-if="reactionTypeStore.types.length === 0" class="flex flex-col items-center py-10 text-center">
        <Smile class="h-6 w-6 text-slate-300" />
        <p class="mt-2 text-xs text-slate-400">{{ t('reactions.admin.emptyDescription') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
 <thead class="bg-slate-50 text-[11px] text-slate-500">
            <tr>
              <th class="px-4 py-2 font-medium">{{ t('reactions.admin.iconHeader') }}</th>
              <th class="px-4 py-2 font-medium">{{ t('reactions.admin.keyHeader') }}</th>
              <th class="px-4 py-2 font-medium">{{ t('reactions.admin.labelHeader') }}</th>
              <th class="px-4 py-2 font-medium">{{ t('reactions.admin.actionsHeader') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="reactionType in reactionTypeStore.types" :key="reactionType.id">
              <td colspan="4" class="p-0">
                <div v-if="editingId === reactionType.id" class="space-y-2 p-4">
                  <input
                    v-model="editLabel"
                    type="text"
                    maxlength="64"
                    :placeholder="t('reactions.admin.labelHeader')"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <input
                    v-model="editEmoji"
                    type="text"
                    maxlength="16"
                    :placeholder="t('reactions.admin.emojiPlaceholder')"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <input
                    type="file"
                    accept="image/*"
                    class="block w-full text-xs text-slate-500"
                    @change="onEditIconChange"
                  />
                  <div class="flex gap-2">
                    <button
                      type="button"
                      :disabled="saving"
                      class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                      @click="onSave(reactionType)"
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
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 text-lg">
                      <img v-if="reactionType.icon_url" :src="reactionType.icon_url" :alt="reactionType.label" class="h-6 w-6" />
                      <template v-else>{{ reactionType.emoji }}</template>
                    </span>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-slate-900">{{ reactionType.label }}</p>
 <p class="mt-0.5 text-[11px] text-slate-400">{{ reactionType.key }}</p>
                    </div>
                  </div>

                  <div class="flex shrink-0 items-center gap-1">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-blue-300 hover:text-blue-600"
                      :title="t('common.edit')"
                      @click="startEdit(reactionType)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-rose-300 hover:text-rose-600"
                      :title="t('common.delete')"
                      @click="pendingDelete = reactionType"
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
          v-model="newKey"
          type="text"
          maxlength="32"
          :placeholder="t('reactions.admin.keyPlaceholder')"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <input
          v-model="newLabel"
          type="text"
          maxlength="64"
          :placeholder="t('reactions.admin.labelPlaceholder')"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <input
          v-model="newEmoji"
          type="text"
          maxlength="16"
          :placeholder="t('reactions.admin.emojiPlaceholder')"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <input
          ref="newIconInput"
          type="file"
          accept="image/*"
          class="block w-full text-xs text-slate-500"
          @change="onNewIconChange"
        />
        <button
          type="button"
          :disabled="creating || !newKey.trim() || !newLabel.trim()"
          class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="onCreate"
        >
          {{ t('reactions.admin.addButton') }}
        </button>
      </div>
    </section>

    <AdminConfirmDialog
      :open="!!pendingDelete"
      :title="t('reactions.admin.confirmDeleteTitle')"
      :message="t('reactions.admin.confirmDeleteMessage', { label: pendingDelete?.label })"
      @confirm="onConfirmDelete"
      @cancel="pendingDelete = null"
    />
  </div>
</template>
