<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Users } from '@lucide/vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useGroupStore } from '../store/groupStore'

const groupStore = useGroupStore()
const { t } = useI18n()
const joiningId = ref<number | null>(null)

onMounted(() => {
  groupStore.fetchPopular()
})

async function onJoin(groupId: number): Promise<void> {
  joiningId.value = groupId
  try {
    await groupStore.join(groupId)
  } finally {
    joiningId.value = null
  }
}
</script>

<template>
  <section class="rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md">
 <h2 class="text-xs font-bold text-cyber-text">
      {{ t('widgets.popularGroups.title') }}
    </h2>

    <div v-if="groupStore.popularLoading" class="mt-3 space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center gap-2.5">
        <div class="h-8 w-8 shrink-0 animate-pulse rounded-full bg-cyber-surface/60" />
        <div class="h-2.5 w-24 animate-pulse rounded-full bg-cyber-surface/60" />
      </div>
    </div>

 <p v-else-if="groupStore.popularError" class="mt-3 text-xs text-cyber-neon-pink">
      {{ t('widgets.popularGroups.error') }}
    </p>

 <p v-else-if="groupStore.popularGroups.length === 0" class="mt-3 text-xs text-cyber-muted">
      {{ t('widgets.popularGroups.empty') }}
    </p>

    <ul v-else class="mt-3 space-y-3">
      <li v-for="group in groupStore.popularGroups" :key="group.id" class="flex items-center justify-between gap-2">
        <router-link
          :to="{ name: 'group-detail', params: { slug: group.slug } }"
          class="group flex min-w-0 items-center gap-2.5"
        >
          <img
            v-if="group.avatar_url"
            :src="group.avatar_url"
            :alt="group.name"
            class="h-8 w-8 shrink-0 rounded-full object-cover"
          />
          <span
            v-else
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface"
          >
            <Users class="h-3.5 w-3.5 text-cyber-neon-indigo" />
          </span>
          <span class="min-w-0">
            <span
 class="block truncate text-xs text-cyber-text transition-colors duration-300 group-hover:text-cyber-neon-cyan"
            >
              {{ group.name }}
            </span>
            <span class="block font-mono text-xs tabular-nums text-cyber-muted">{{ group.members_count }}</span>
          </span>
        </router-link>

        <AppButton
          v-if="!group.viewer_membership"
          :label="t('widgets.popularGroups.join')"
          variant="secondary"
          :loading="joiningId === group.id"
          @click="onJoin(group.id)"
        />
      </li>
    </ul>
  </section>
</template>
