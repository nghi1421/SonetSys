<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Globe, Lock, Users } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import type { Group } from '../types'

const props = defineProps<{ group: Group; joining: boolean }>()

const emit = defineEmits<{ join: [] }>()

const membershipLabel = computed(() => {
  const membership = props.group.viewer_membership
  if (!membership) return null
  if (membership.role === 'owner') return 'Owner'
  return membership.status === 'pending' ? 'Requested' : 'Member'
})

const membershipDotClass = computed(() => {
  const membership = props.group.viewer_membership
  if (!membership) return ''
  if (membership.status === 'pending') return 'bg-amber-400'
  return 'bg-cyber-neon-cyan'
})
</script>

<template>
  <RouterLink
    :to="{ name: 'group-detail', params: { slug: group.slug } }"
    class="flex flex-col gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
  >
    <div class="flex items-start justify-between gap-2">
      <h3 class="text-xs font-bold tracking-wider text-cyber-text">// {{ group.name }}</h3>

      <span
        class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest"
        :class="
          group.visibility === 'public'
            ? 'border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 text-cyber-neon-cyan'
            : 'border-cyber-neon-pink/30 bg-cyber-neon-pink/10 text-cyber-neon-pink'
        "
      >
        <Globe v-if="group.visibility === 'public'" class="h-2.5 w-2.5" />
        <Lock v-else class="h-2.5 w-2.5" />
        {{ group.visibility }}
      </span>
    </div>

    <p v-if="group.description" class="line-clamp-2 font-mono text-xs leading-relaxed text-cyber-muted">
      {{ group.description }}
    </p>

    <div class="mt-auto flex items-center justify-between gap-2 border-t border-cyber-border pt-3">
      <span class="inline-flex items-center gap-1.5 font-mono text-[10px] tabular-nums text-cyber-muted">
        <Users class="h-3 w-3" />
        {{ group.members_count }}
      </span>

      <span
        v-if="membershipLabel"
        class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-surface px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-text"
      >
        <span class="h-1.5 w-1.5 rounded-full" :class="membershipDotClass" />
        {{ membershipLabel }}
      </span>

      <AppButton
        v-else
        label="Join"
        variant="secondary"
        :loading="joining"
        @click.prevent="emit('join')"
      />
    </div>
  </RouterLink>
</template>
