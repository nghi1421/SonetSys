<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppInput from '@/shared/components/ui/AppInput.vue'
import AppModal from '@/shared/components/ui/AppModal.vue'
import AppTextarea from '@/shared/components/ui/AppTextarea.vue'
import { useGroupStore } from '../store/groupStore'
import type { GroupVisibility } from '../types'

const emit = defineEmits<{ close: [] }>()

const groupStore = useGroupStore()
const { t } = useI18n()

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
    error.value = t('groups.createGroupModal.createError')
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <AppModal :open="true" size="md" @close="emit('close')">
    <form class="space-y-4" @submit.prevent="onSubmit">
      <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">
        // {{ t('groups.createGroupModal.title') }}
      </h2>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <AppInput v-model="name" :label="t('groups.createGroupModal.nameLabel')" />

      <AppTextarea
        v-model="description"
        :label="t('groups.createGroupModal.descriptionLabel')"
        :rows="3"
        :maxlength="5000"
        :placeholder="t('groups.createGroupModal.descriptionPlaceholder')"
      />

      <div class="space-y-1.5">
        <label class="block text-[9px] font-mono uppercase tracking-widest text-cyber-neon-cyan">
          {{ t('groups.createGroupModal.visibilityLabel') }}
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
            {{ t('groups.visibility.public') }}
            <span class="mt-0.5 block font-mono text-[9px] normal-case text-cyber-muted">{{
              t('groups.visibility.publicHint')
            }}</span>
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
            {{ t('groups.visibility.private') }}
            <span class="mt-0.5 block font-mono text-[9px] normal-case text-cyber-muted">{{
              t('groups.visibility.privateHint')
            }}</span>
          </button>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          @click="emit('close')"
        >
          {{ t('common.cancel') }}
        </button>
        <AppButton
          type="submit"
          :label="t('groups.createGroupModal.submit')"
          :loading="creating"
          :disabled="!name.trim()"
        />
      </div>
    </form>
  </AppModal>
</template>
