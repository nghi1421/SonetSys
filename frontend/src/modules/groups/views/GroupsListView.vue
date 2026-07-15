<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Users } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import CreateGroupModal from '../components/CreateGroupModal.vue'
import GroupCard from '../components/GroupCard.vue'
import { useGroupStore } from '../store/groupStore'

const groupStore = useGroupStore()

const showCreateModal = ref(false)
const joiningGroupId = ref<number | null>(null)
const error = ref<string | null>(null)

onMounted(() => {
  groupStore.fetchGroups()
})

async function onJoin(groupId: number): Promise<void> {
  joiningGroupId.value = groupId
  error.value = null
  try {
    await groupStore.join(groupId)
    await groupStore.fetchGroups()
  } catch {
    error.value = 'Could not join this group. Please try again.'
  } finally {
    joiningGroupId.value = null
  }
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-4xl space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Groups</h1>
        <AppButton label="Create Group" @click="showCreateModal = true" />
      </div>

      <AppAlert v-if="error">{{ error }}</AppAlert>

      <div v-if="groupStore.loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
          v-for="i in 4"
          :key="i"
          class="h-36 animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 backdrop-blur-md"
        />
      </div>

      <div v-else-if="groupStore.groups.length === 0" class="flex flex-col items-center py-16 text-center">
        <Users class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">No groups yet</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">Create the first group for your community.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <GroupCard
          v-for="group in groupStore.groups"
          :key="group.id"
          :group="group"
          :joining="joiningGroupId === group.id"
          @join="onJoin(group.id)"
        />
      </div>
    </div>

    <CreateGroupModal v-if="showCreateModal" @close="showCreateModal = false" />
  </AppShell>
</template>
