export interface ReactionTypeDef {
  id: number
  key: string
  label: string
  emoji: string | null
  icon_url: string | null
  sort_order: number
}

export interface CreateReactionTypePayload {
  key: string
  label: string
  emoji?: string
  icon?: File
  sort_order?: number
}

export interface UpdateReactionTypePayload {
  label: string
  emoji?: string
  icon?: File
  sort_order?: number
}
