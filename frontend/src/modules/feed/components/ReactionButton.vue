<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Heart } from '@lucide/vue'
import { useReactionTypeStore } from '@/modules/reactions/store/reactionTypeStore'
import type { ReactionType } from '../types'

const props = withDefaults(
  defineProps<{ count: number; myReaction: ReactionType | null; variant?: 'pill' | 'inline' }>(),
  { variant: 'pill' },
)

const emit = defineEmits<{ react: [type: ReactionType]; unreact: [] }>()

const reactionTypeStore = useReactionTypeStore()
const { t } = useI18n()

const showPicker = ref(false)

onMounted(() => {
  reactionTypeStore.fetchTypesOnce()
})

// A stored my_reaction can point at a key an admin has since deleted — falls
// back to null (renders the generic Heart glyph) rather than crashing.
const myReactionOption = computed(
  () => reactionTypeStore.types.find((option) => option.key === props.myReaction) ?? null,
)

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
      <Heart v-if="!myReactionOption" class="h-3 w-3" />
      <img v-else-if="myReactionOption.icon_url" :src="myReactionOption.icon_url" :alt="myReactionOption.label" class="h-3.5 w-3.5" />
      <span v-else class="text-xs leading-none">{{ myReactionOption.emoji }}</span>
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
      <Heart v-if="!myReactionOption" class="h-3 w-3" />
      <img v-else-if="myReactionOption.icon_url" :src="myReactionOption.icon_url" :alt="myReactionOption.label" class="h-3.5 w-3.5" />
      <span v-else class="text-xs leading-none">{{ myReactionOption.emoji }}</span>
      {{ count }}
    </button>

    <div v-if="showPicker" class="fixed inset-0 z-0" @click="closePicker" />

    <div
      v-if="showPicker"
      class="popover-panel absolute left-0 top-full z-10 mt-2 flex items-center gap-1 rounded-hud border border-cyber-border bg-cyber-surface p-1.5"
      @click.stop
    >
      <button
        v-for="option in reactionTypeStore.types"
        :key="option.id"
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full text-lg transition-all duration-300 hover:scale-125 hover:bg-cyber-surface/60"
        :class="myReaction === option.key && 'bg-cyber-neon-pink/10 shadow-pink-glow'"
        :aria-label="option.label"
        @click="pick(option.key)"
      >
        <img v-if="option.icon_url" :src="option.icon_url" :alt="option.label" class="h-5 w-5" />
        <template v-else>{{ option.emoji }}</template>
      </button>
    </div>
  </div>
</template>
