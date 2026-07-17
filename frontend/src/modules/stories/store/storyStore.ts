import { defineStore } from 'pinia'
import { ref } from 'vue'
import { storyApi } from '../api/storyApi'
import type { CreateStoryPayload, StoryGroup, StoryViewer } from '../types'

export const useStoryStore = defineStore('stories', () => {
  const storyGroups = ref<StoryGroup[]>([])
  const loading = ref(false)
  const viewersByStory = ref<Record<number, StoryViewer[]>>({})

  const activeViewerGroupIndex = ref<number | null>(null)
  const activeViewerStoryIndex = ref(0)

  async function fetchActiveStories(): Promise<void> {
    loading.value = true
    try {
      const response = await storyApi.fetchActiveStories()
      storyGroups.value = response.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function createStory(payload: CreateStoryPayload): Promise<void> {
    const response = await storyApi.createStory(payload)
    if (!response.data) return

    const story = response.data
    const existingGroup = storyGroups.value.find((group) => group.author.id === story.author.id)
    if (existingGroup) {
      existingGroup.stories.push(story)
    } else {
      storyGroups.value = [{ author: story.author, stories: [story] }, ...storyGroups.value]
    }
  }

  async function deleteStory(storyId: number): Promise<void> {
    await storyApi.deleteStory(storyId)

    storyGroups.value = storyGroups.value
      .map((group) => ({
        ...group,
        stories: group.stories.filter((story) => story.id !== storyId),
      }))
      .filter((group) => group.stories.length > 0)
  }

  async function markViewed(storyId: number): Promise<void> {
    await storyApi.markViewed(storyId)

    for (const group of storyGroups.value) {
      const story = group.stories.find((item) => item.id === storyId)
      if (story) story.viewed_by_me = true
    }
  }

  async function fetchViewers(storyId: number): Promise<void> {
    const response = await storyApi.fetchViewers(storyId)
    viewersByStory.value[storyId] = response.data ?? []
  }

  function openViewer(groupIndex: number): void {
    activeViewerGroupIndex.value = groupIndex
    activeViewerStoryIndex.value = 0
  }

  function closeViewer(): void {
    activeViewerGroupIndex.value = null
    activeViewerStoryIndex.value = 0
  }

  return {
    storyGroups,
    loading,
    viewersByStory,
    activeViewerGroupIndex,
    activeViewerStoryIndex,
    fetchActiveStories,
    createStory,
    deleteStory,
    markViewed,
    fetchViewers,
    openViewer,
    closeViewer,
  }
})
