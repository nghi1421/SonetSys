<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, ShieldMinus, ShieldPlus, Users, X } from '@lucide/vue'
import AppAlert from '@/shared/components/ui/AppAlert.vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useGroupStore } from '../store/groupStore'
import type { Group, GroupMember } from '../types'

const props = defineProps<{ group: Group; isOwner: boolean; isManager: boolean }>()

const groupStore = useGroupStore()
const authStore = useAuthStore()
const { t } = useI18n()

const error = ref<string | null>(null)
const busyUserId = ref<number | null>(null)

onMounted(async () => {
  await groupStore.fetchMembers(props.group.id)
  if (props.isManager) {
    await groupStore.fetchRequests(props.group.id)
  }
})

async function withErrorHandling(userId: number, action: () => Promise<void>): Promise<void> {
  busyUserId.value = userId
  error.value = null
  try {
    await action()
  } catch {
    error.value = t('common.somethingWentWrong')
  } finally {
    busyUserId.value = null
  }
}

function onApprove(userId: number): Promise<void> {
  return withErrorHandling(userId, () => groupStore.approveRequest(props.group.id, userId))
}

function onReject(userId: number): Promise<void> {
  return withErrorHandling(userId, () => groupStore.removeMember(props.group.id, userId))
}

function onRemove(userId: number): Promise<void> {
  return withErrorHandling(userId, () => groupStore.removeMember(props.group.id, userId))
}

function onPromote(userId: number): Promise<void> {
  return withErrorHandling(userId, () => groupStore.promoteMember(props.group.id, userId))
}

function onDemote(userId: number): Promise<void> {
  return withErrorHandling(userId, () => groupStore.demoteMember(props.group.id, userId))
}

function canRemove(member: GroupMember): boolean {
  if (member.role === 'owner' || member.user.id === authStore.user?.id) return false
  if (!props.isManager) return false
  if (member.role === 'admin' && !props.isOwner) return false
  return true
}
</script>

<template>
  <div class="space-y-4">
    <AppAlert v-if="error">{{ error }}</AppAlert>

    <section v-if="isManager" class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
      <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-pink">{{ t('groups.groupMembers.joinRequestsTitle') }}</h2>

      <div v-if="groupStore.loadingRequests" class="mt-4 space-y-2">
        <div v-for="i in 2" :key="i" class="h-10 animate-pulse rounded-hud bg-cyber-surface/60" />
      </div>

      <p v-else-if="groupStore.requests.length === 0" class="mt-3 font-mono text-xs text-cyber-muted">
        {{ t('groups.groupMembers.noPendingRequests') }}
      </p>

      <ul v-else class="mt-4 divide-y divide-cyber-border">
        <li v-for="member in groupStore.requests" :key="member.id" class="flex items-center justify-between gap-3 py-3">
          <p class="truncate text-xs font-bold text-cyber-text">{{ member.user.name }}</p>
          <div class="flex shrink-0 items-center gap-1">
            <button
              type="button"
              :disabled="busyUserId === member.user.id"
              class="rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 p-1.5 text-cyber-neon-cyan transition-all duration-300 hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :title="t('groups.groupMembers.approve')"
              @click="member.user.id !== null && onApprove(member.user.id)"
            >
              <Check class="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              :disabled="busyUserId === member.user.id"
              class="rounded-full border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 p-1.5 text-cyber-neon-pink transition-all duration-300 hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :title="t('groups.groupMembers.reject')"
              @click="member.user.id !== null && onReject(member.user.id)"
            >
              <X class="h-3.5 w-3.5" />
            </button>
          </div>
        </li>
      </ul>
    </section>

    <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
      <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">
        {{ t('groups.groupMembers.membersTitle') }} · {{ group.members_count }}
      </h2>

      <div v-if="groupStore.loadingMembers" class="mt-4 space-y-2">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded-hud bg-cyber-surface/60" />
      </div>

      <div v-else-if="groupStore.members.length === 0" class="mt-4 flex flex-col items-center py-8 text-center">
        <Users class="h-6 w-6 text-cyber-muted" />
        <p class="mt-2 font-mono text-xs text-cyber-muted">{{ t('groups.groupMembers.noMembersYet') }}</p>
      </div>

      <ul v-else class="mt-4 divide-y divide-cyber-border">
        <li v-for="member in groupStore.members" :key="member.id" class="flex items-center justify-between gap-3 py-3">
          <div class="min-w-0">
            <p class="truncate text-xs font-bold text-cyber-text">{{ member.user.name }}</p>
            <p class="mt-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-muted">{{ member.role }}</p>
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <button
              v-if="isOwner && member.role === 'member' && member.user.id !== authStore.user?.id"
              type="button"
              :disabled="busyUserId === member.user.id"
              class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :title="t('groups.groupMembers.makeAdmin')"
              @click="member.user.id !== null && onPromote(member.user.id)"
            >
              <ShieldPlus class="h-3.5 w-3.5" />
            </button>
            <button
              v-if="isOwner && member.role === 'admin'"
              type="button"
              :disabled="busyUserId === member.user.id"
              class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/50 hover:text-cyber-neon-indigo hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :title="t('groups.groupMembers.removeAdmin')"
              @click="member.user.id !== null && onDemote(member.user.id)"
            >
              <ShieldMinus class="h-3.5 w-3.5" />
            </button>
            <button
              v-if="canRemove(member)"
              type="button"
              :disabled="busyUserId === member.user.id"
              class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
              :title="t('groups.groupMembers.removeMember')"
              @click="member.user.id !== null && onRemove(member.user.id)"
            >
              <X class="h-3.5 w-3.5" />
            </button>
          </div>
        </li>
      </ul>
    </section>
  </div>
</template>
