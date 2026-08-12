<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { UserPlus } from '@lucide/vue'
import type { useMentionPicker } from '../composables/useMentionPicker'
import type { MentionCandidate } from '../types'

const props = defineProps<{
  mention: ReturnType<typeof useMentionPicker>
  triggerLabel: string
}>()

const emit = defineEmits<{ select: [candidate: MentionCandidate] }>()

const { t } = useI18n()

const PANEL_WIDTH = 256
const GAP = 8
const VIEWPORT_MARGIN = 8

const triggerRef = ref<HTMLButtonElement | null>(null)
const panelStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

function updatePosition(): void {
  const trigger = triggerRef.value
  if (!trigger) return

  const rect = trigger.getBoundingClientRect()
  const maxLeft = window.innerWidth - PANEL_WIDTH - VIEWPORT_MARGIN
  const left = Math.min(Math.max(rect.right - PANEL_WIDTH, VIEWPORT_MARGIN), maxLeft)

  panelStyle.value = { top: `${rect.bottom + GAP}px`, left: `${left}px` }
}

function bindReposition(): void {
  window.addEventListener('scroll', updatePosition, true)
  window.addEventListener('resize', updatePosition)
}

function unbindReposition(): void {
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
}

async function onToggle(): Promise<void> {
  props.mention.toggle()

  if (props.mention.showPicker.value) {
    await nextTick()
    updatePosition()
    bindReposition()
  } else {
    unbindReposition()
  }
}

function onClose(): void {
  props.mention.close()
  unbindReposition()
}

function onSelect(candidate: MentionCandidate): void {
  emit('select', candidate)
  unbindReposition()
}

onBeforeUnmount(unbindReposition)
</script>

<template>
  <div class="relative">
    <button
      ref="triggerRef"
      type="button"
      class="rounded-full p-1.5 text-cyber-muted transition-all duration-300 hover:text-cyber-neon-cyan"
      :aria-label="triggerLabel"
      @click="onToggle"
    >
      <UserPlus class="h-4 w-4" />
    </button>

    <Teleport to="body">
      <div
        v-if="mention.showPicker.value"
        class="fixed inset-0 z-[70]"
        @click="onClose"
      />

      <div
        v-if="mention.showPicker.value"
        class="popover-panel fixed z-[71] w-64 rounded-hud border border-cyber-border bg-cyber-surface p-3 shadow-cyan-glow"
        :style="panelStyle"
        @click.stop
      >
        <input
          v-model="mention.query.value"
          type="text"
          :placeholder="t('feed.postComposer.mentionSearchPlaceholder')"
 class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-1.5 text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          @input="mention.onSearchInput()"
        />

        <ul v-if="mention.results.value.length" class="mt-2 max-h-48 space-y-1 overflow-y-auto">
          <li v-for="candidate in mention.results.value" :key="candidate.id">
            <button
              type="button"
 class="block w-full rounded-hud px-2 py-1.5 text-left text-xs text-cyber-text transition-all duration-300 hover:bg-cyber-surface/60 hover:text-cyber-neon-cyan"
              @click="onSelect(candidate)"
            >
              {{ candidate.name }}
            </button>
          </li>
        </ul>
        <p
          v-else-if="mention.searching.value"
 class="mt-2 text-xs text-cyber-muted"
        >
          {{ t('common.loading') }}
        </p>
        <p
          v-else-if="mention.query.value.trim().length >= 2"
 class="mt-2 text-xs text-cyber-muted"
        >
          {{ t('feed.postComposer.mentionNoResults') }}
        </p>
      </div>
    </Teleport>
  </div>
</template>
