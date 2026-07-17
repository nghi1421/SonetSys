import { http } from '@/shared/api/http'
import type { ApiResponse } from '@/shared/api/types'
import type { CreateStoryPayload, Story, StoryGroup, StoryViewer } from '../types'

function toRequestBody(payload: CreateStoryPayload): FormData {
  const form = new FormData()
  form.append('media', payload.media)
  form.append('media_type', payload.media_type)
  if (payload.caption) form.append('caption', payload.caption)
  return form
}

export const storyApi = {
  async fetchActiveStories() {
    const { data } = await http.get<ApiResponse<StoryGroup[]>>('/stories')
    return data
  },

  async createStory(payload: CreateStoryPayload) {
    const { data } = await http.post<ApiResponse<Story>>('/stories', toRequestBody(payload))
    return data
  },

  async deleteStory(storyId: number) {
    const { data } = await http.delete<ApiResponse<null>>(`/stories/${storyId}`)
    return data
  },

  async markViewed(storyId: number) {
    const { data } = await http.post<ApiResponse<null>>(`/stories/${storyId}/view`)
    return data
  },

  async fetchViewers(storyId: number) {
    const { data } = await http.get<ApiResponse<StoryViewer[]>>(`/stories/${storyId}/viewers`)
    return data
  },
}
