import type { MentionCandidate, Post } from '@/modules/feed/types'
import type { Group } from '@/modules/groups/types'

export interface SearchResults {
  posts: Post[]
  users: MentionCandidate[]
  groups: Group[]
  hashtags: string[]
}
