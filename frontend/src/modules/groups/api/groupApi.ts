import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateGroupPayload, Group, GroupMember, UpdateGroupPayload } from '../types'

export const groupApi = {
  async list() {
    const { data } = await http.get<ApiResponse<Group[]>>('/groups')
    return data
  },

  async show(slug: string) {
    const { data } = await http.get<ApiResponse<Group>>(`/groups/${slug}`)
    return data
  },

  async create(payload: CreateGroupPayload) {
    const { data } = await http.post<ApiResponse<Group>>('/groups', payload)
    return data
  },

  async update(groupId: number, payload: UpdateGroupPayload) {
    const { data } = await http.put<ApiResponse<Group>>(`/groups/${groupId}`, payload)
    return data
  },

  async remove(groupId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/groups/${groupId}`)
    return data
  },

  async join(groupId: number) {
    const { data } = await http.post<ApiResponse<GroupMember>>(`/groups/${groupId}/join`)
    return data
  },

  async leave(groupId: number) {
    const { data } = await http.post<ApiResponse<null>>(`/groups/${groupId}/leave`)
    return data
  },

  async members(groupId: number) {
    const { data } = await http.get<ApiResponse<GroupMember[]>>(`/groups/${groupId}/members`)
    return data
  },

  async requests(groupId: number) {
    const { data } = await http.get<ApiResponse<GroupMember[]>>(`/groups/${groupId}/requests`)
    return data
  },

  async approve(groupId: number, userId: number) {
    const { data } = await http.post<ApiResponse<GroupMember>>(`/groups/${groupId}/requests/${userId}/approve`)
    return data
  },

  async removeMember(groupId: number, userId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/groups/${groupId}/members/${userId}`)
    return data
  },
}
