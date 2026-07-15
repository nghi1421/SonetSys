<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useGroupStore } from '../store/groupStore'
import type { Group, GroupVisibility } from '../types'

const props = defineProps<{ group: Group; isOwner: boolean }>()

const router = useRouter()
const groupStore = useGroupStore()

const name = ref(props.group.name)
const description = ref(props.group.description ?? '')
const visibility = ref<GroupVisibility>(props.group.visibility)
const saving = ref(false)
const error = ref<string | null>(null)
const confirmingDelete = ref(false)
const deleting = ref(false)

async function onSave(): Promise<void> {
  if (!name.value.trim()) return

  saving.value = true
  error.value = null

  try {
    await groupStore.updateGroup(props.group.id, {
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      visibility: visibility.value,
    })
  } catch {
    error.value = 'Could not save changes. Please try again.'
  } finally {
    saving.value = false
  }
}

async function onConfirmDelete(): Promise<void> {
  confirmingDelete.value = false
  deleting.value = true
  try {
    await groupStore.deleteGroup(props.group.id)
    router.push({ name: 'groups-list' })
  } catch {
    error.value = 'Could not delete this group. Please try again.'
    deleting.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <AppAlert v-if="error">{{ error }}</AppAlert>

    <form class="space-y-4 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @submit.prevent="onSave">
      <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">Group Settings</h2>

      <AppInput v-model="name" label="Name" />

      <div class="space-y-1.5">
        <label class="block text-[9px] font-mono uppercase tracking-widest text-cyber-neon-cyan">
          Description
        </label>
        <textarea
          v-model="description"
          rows="3"
          class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
        />
      </div>

      <div class="space-y-1.5">
        <label class="block text-[9px] font-mono uppercase tracking-widest text-cyber-neon-cyan">
          Visibility
        </label>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="rounded-hud border px-3 py-2 text-left font-mono text-xs transition-all duration-300"
            :class="
              visibility === 'public'
                ? 'border-cyber-neon-cyan/50 bg-cyber-neon-cyan/10 text-cyber-neon-cyan shadow-cyan-glow'
                : 'border-cyber-border bg-cyber-surface/60 text-cyber-muted hover:border-cyber-neon-cyan/30'
            "
            @click="visibility = 'public'"
          >
            Public
          </button>
          <button
            type="button"
            class="rounded-hud border px-3 py-2 text-left font-mono text-xs transition-all duration-300"
            :class="
              visibility === 'private'
                ? 'border-cyber-neon-pink/50 bg-cyber-neon-pink/10 text-cyber-neon-pink shadow-pink-glow'
                : 'border-cyber-border bg-cyber-surface/60 text-cyber-muted hover:border-cyber-neon-pink/30'
            "
            @click="visibility = 'private'"
          >
            Private
          </button>
        </div>
      </div>

      <AppButton type="submit" label="Save Changes" :loading="saving" :disabled="!name.trim()" />
    </form>

    <section v-if="isOwner" class="rounded-hud border border-cyber-neon-pink/30 bg-cyber-neon-pink/5 p-5 backdrop-blur-md">
      <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-pink">Danger Zone</h2>
      <p class="mt-2 font-mono text-xs text-cyber-muted">
        Deleting a group removes it and its posts for every member. This can't be undone.
      </p>
      <button
        type="button"
        class="mt-4 rounded-full border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 px-4 py-1.5 font-mono text-xs font-bold uppercase tracking-wider text-cyber-neon-pink transition-all duration-300 hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
        :disabled="deleting"
        @click="confirmingDelete = true"
      >
        Delete Group
      </button>
    </section>

    <ConfirmDialog
      v-if="isOwner"
      :open="confirmingDelete"
      title="Delete this group?"
      message="This permanently deletes the group and its posts for every member."
      @confirm="onConfirmDelete"
      @cancel="confirmingDelete = false"
    />
  </div>
</template>
