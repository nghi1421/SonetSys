import type { StoryGroup } from '../types'

export function hasUnviewed(group: StoryGroup): boolean {
  return group.stories.some((story) => !story.viewed_by_me)
}

export function isMine(group: StoryGroup, currentUserId: number | null | undefined): boolean {
  return group.author.id === currentUserId
}
