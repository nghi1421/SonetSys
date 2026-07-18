<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Heart } from '@lucide/vue'
import { REACTION_EMOJI, type ReactionType } from '../types'

const REACTION_TYPES: ReactionType[] = ['like', 'love', 'haha', 'wow', 'sad', 'angry']

const props = withDefaults(
  defineProps<{ count: number; myReaction: ReactionType | null; variant?: 'pill' | 'inline' }>(),
  { variant: 'pill' },
)

const emit = defineEmits<{ react: [type: ReactionType]; unreact: [] }>()

const { t } = useI18n()

const showPicker = ref(false)

function closePicker(): void {
  showPicker.value = false
}

function togglePicker(): void {
  showPicker.value = !showPicker.value
}

function pick(type: ReactionType): void {
  if (type === props.myReaction) {
    emit('unreact')
  } else {
    emit('react', type)
  }
  closePicker()
}
</script>

<template>
  <div class="relative inline-flex">
    <button
      v-if="variant === 'pill'"
      type="button"
      class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-mono text-[10px] transition-all duration-300"
      :class="
        myReaction
          ? 'border-cyber-neon-pink/40 bg-cyber-neon-pink/10 text-cyber-neon-pink shadow-pink-glow'
          : 'border-cyber-border bg-cyber-glass text-cyber-muted hover:border-cyber-neon-pink/40 hover:text-cyber-neon-pink'
      "
      :aria-label="t('feed.reactions.reactLabel')"
      @click="togglePicker"
    >
      <Heart v-if="!myReaction" class="h-3 w-3" />
      <span v-else class="text-xs leading-none">{{ REACTION_EMOJI[myReaction] }}</span>
      {{ count }}
    </button>

    <button
      v-else
      type="button"
      class="flex items-center gap-1 normal-case tracking-normal transition-colors duration-300"
      :class="myReaction ? 'text-cyber-neon-pink' : 'hover:text-cyber-neon-pink'"
      :aria-label="t('feed.reactions.reactLabel')"
      @click="togglePicker"
    >
      <Heart v-if="!myReaction" class="h-3 w-3" />
      <span v-else class="text-xs leading-none">{{ REACTION_EMOJI[myReaction] }}</span>
      {{ count }}
    </button>

    <div v-if="showPicker" class="fixed inset-0 z-0" @click="closePicker" />

    <div
      v-if="showPicker"
      class="popover-panel absolute left-0 top-full z-10 mt-2 flex items-center gap-1 rounded-hud border border-cyber-border bg-cyber-glass p-1.5 backdrop-blur-md"
      @click.stop
    >
      <button
        v-for="type in REACTION_TYPES"
        :key="type"
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full text-lg transition-all duration-300 hover:scale-125 hover:bg-cyber-surface/60"
        :class="myReaction === type && 'bg-cyber-neon-pink/10 shadow-pink-glow'"
        :aria-label="t(`feed.reactions.${type}`)"
        @click="pick(type)"
      >
        {{ REACTION_EMOJI[type] }}
      </button>
    </div>
  </div>
</template>
