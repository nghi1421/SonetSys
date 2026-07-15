import { defineStore } from 'pinia'
import { ref } from 'vue'
import { groupApi } from '../api/groupApi'
import type { CreateGroupPayload, Group, GroupMember, UpdateGroupPayload } from '../types'

export const useGroupStore = defineStore('groups', () => {
  const groups = ref<Group[]>([])
  const loading = ref(false)

  const currentGroup = ref<Group | null>(null)
  const loadingCurrent = ref(false)

  const members = ref<GroupMember[]>([])
  const loadingMembers = ref(false)

  const requests = ref<GroupMember[]>([])
  const loadingRequests = ref(false)

  async function fetchGroups(): Promise<void> {
    loading.value = true
    try {
      const response = await groupApi.list()
      groups.value = response.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function createGroup(payload: CreateGroupPayload): Promise<Group | null> {
    const response = await groupApi.create(payload)
    if (response.data) {
      groups.value = [response.data, ...groups.value]
    }
    return response.data
  }

  async function fetchGroup(slug: string): Promise<void> {
    loadingCurrent.value = true
    try {
      const response = await groupApi.show(slug)
      currentGroup.value = response.data
    } finally {
      loadingCurrent.value = false
    }
  }

  async function updateGroup(groupId: number, payload: UpdateGroupPayload): Promise<void> {
    const response = await groupApi.update(groupId, payload)
    if (response.data && currentGroup.value?.id === groupId) {
      currentGroup.value = response.data
    }
  }

  async function deleteGroup(groupId: number): Promise<void> {
    await groupApi.remove(groupId)
    groups.value = groups.value.filter((group) => group.id !== groupId)
    if (currentGroup.value?.id === groupId) currentGroup.value = null
  }

  async function join(groupId: number): Promise<void> {
    const response = await groupApi.join(groupId)
    if (response.data && currentGroup.value?.id === groupId) {
      const wasApproved = currentGroup.value.viewer_membership?.status === 'approved'
      currentGroup.value = {
        ...currentGroup.value,
        viewer_membership: { role: response.data.role, status: response.data.status },
        members_count:
          response.data.status === 'approved' && !wasApproved
            ? currentGroup.value.members_count + 1
            : currentGroup.value.members_count,
      }
    }
  }

  async function leave(groupId: number): Promise<void> {
    await groupApi.leave(groupId)
    if (currentGroup.value?.id === groupId) {
      currentGroup.value = {
        ...currentGroup.value,
        viewer_membership: null,
        members_count: Math.max(0, currentGroup.value.members_count - 1),
      }
    }
  }

  async function fetchMembers(groupId: number): Promise<void> {
    loadingMembers.value = true
    try {
      const response = await groupApi.members(groupId)
      members.value = response.data ?? []
    } finally {
      loadingMembers.value = false
    }
  }

  async function fetchRequests(groupId: number): Promise<void> {
    loadingRequests.value = true
    try {
      const response = await groupApi.requests(groupId)
      requests.value = response.data ?? []
    } finally {
      loadingRequests.value = false
    }
  }

  async function approveRequest(groupId: number, userId: number): Promise<void> {
    const response = await groupApi.approve(groupId, userId)
    requests.value = requests.value.filter((member) => member.user.id !== userId)
    if (response.data) {
      members.value = [...members.value, response.data]
    }
    if (currentGroup.value?.id === groupId) {
      currentGroup.value = { ...currentGroup.value, members_count: currentGroup.value.members_count + 1 }
    }
  }

  async function removeMember(groupId: number, userId: number): Promise<void> {
    await groupApi.removeMember(groupId, userId)
    members.value = members.value.filter((member) => member.user.id !== userId)
    if (currentGroup.value?.id === groupId) {
      currentGroup.value = {
        ...currentGroup.value,
        members_count: Math.max(0, currentGroup.value.members_count - 1),
      }
    }
  }

  async function promoteMember(groupId: number, userId: number): Promise<void> {
    const response = await groupApi.promoteMember(groupId, userId)
    if (response.data) {
      members.value = members.value.map((member) => (member.user.id === userId ? response.data! : member))
    }
  }

  async function demoteMember(groupId: number, userId: number): Promise<void> {
    const response = await groupApi.demoteMember(groupId, userId)
    if (response.data) {
      members.value = members.value.map((member) => (member.user.id === userId ? response.data! : member))
    }
  }

  return {
    groups,
    loading,
    currentGroup,
    loadingCurrent,
    members,
    loadingMembers,
    requests,
    loadingRequests,
    fetchGroups,
    createGroup,
    fetchGroup,
    updateGroup,
    deleteGroup,
    join,
    leave,
    fetchMembers,
    fetchRequests,
    approveRequest,
    removeMember,
    promoteMember,
    demoteMember,
  }
})
