<script setup lang="ts">
import { ref } from 'vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import { useGroupStore } from '../store/groupStore'
import type { GroupVisibility } from '../types'

const emit = defineEmits<{ close: [] }>()

const groupStore = useGroupStore()

const name = ref('')
const description = ref('')
const visibility = ref<GroupVisibility>('public')
const creating = ref(false)
const error = ref<string | null>(null)

async function onSubmit(): Promise<void> {
  if (!name.value.trim()) return

  creating.value = true
  error.value = null

  try {
    const group = await groupStore.createGroup({
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      visibility: visibility.value,
    })
    if (group) emit('close')
  } catch {
    error.value = 'Could not create the group. Please try again.'
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-cyber-bg/80 px-4 backdrop-blur-sm"
    @click="emit('close')"
  >
    <form
      class="w-full max-w-md space-y-4 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md"
      @click.stop
      @submit.prevent="onSubmit"
    >
      <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">// New Group</h2>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <AppInput v-model="name" label="Name" />

      <div class="space-y-1.5">
        <label class="block text-[9px] font-mono uppercase tracking-widest text-cyber-neon-cyan">
          Description
        </label>
        <textarea
          v-model="description"
          rows="3"
          maxlength="5000"
          placeholder="What's this group about?"
          class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-glass px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40 focus:ring-offset-2 focus:ring-offset-cyber-bg"
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
            <span class="mt-0.5 block font-mono text-[9px] normal-case text-cyber-muted">Anyone can join</span>
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
            <span class="mt-0.5 block font-mono text-[9px] normal-case text-cyber-muted">Requires approval</span>
          </button>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          @click="emit('close')"
        >
          Cancel
        </button>
        <AppButton type="submit" label="Create Group" :loading="creating" :disabled="!name.trim()" />
      </div>
    </form>
  </div>
</template>
