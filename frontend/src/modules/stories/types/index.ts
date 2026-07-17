export interface StoryAuthor {
  id: number | null
  name: string | null
}

export type StoryMediaType = 'image' | 'video'

export interface Story {
  id: number
  author: StoryAuthor
  media_type: StoryMediaType
  media_url: string
  caption: string | null
  published_at: string
  expires_at: string
  viewed_by_me: boolean
  views_count: number | null
}

export interface StoryGroup {
  author: StoryAuthor
  stories: Story[]
}

export interface StoryViewer {
  viewer: StoryAuthor
  viewed_at: string
}

export interface CreateStoryPayload {
  media: File
  media_type: StoryMediaType
  caption?: string
}
