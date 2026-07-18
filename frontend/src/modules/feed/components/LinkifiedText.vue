<script setup lang="ts">
import { computed } from 'vue'
import type { PostAuthor } from '../types'

interface HashtagSegment {
  type: 'hashtag'
  content: string
  tag: string
}

interface MentionSegment {
  type: 'mention'
  content: string
  userId: number
}

interface TextSegment {
  type: 'text'
  content: string
}

type Segment = HashtagSegment | MentionSegment | TextSegment

const props = defineProps<{
  text: string
  hashtags: string[]
  mentions: PostAuthor[]
}>()

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

// Only #tag/@Name substrings that exactly match a recorded hashtag/mention
// are ever linkified — anything else (a stray "#" or a manually typed
// "@SomeRandomText" that was never picked from the Mention picker) stays
// plain text. Never v-html: this only ever produces text nodes and
// RouterLinks, so it can't inject markup from user content.
const segments = computed<Segment[]>(() => {
  const validMentions = props.mentions.filter(
    (mention): mention is { id: number; name: string } => mention.id !== null && mention.name !== null,
  )

  const patternParts: string[] = []
  if (props.hashtags.length) {
    const escapedTags = props.hashtags.map(escapeRegExp).join('|')
    patternParts.push(`#(?:${escapedTags})(?![\\p{L}\\p{N}_])`)
  }
  if (validMentions.length) {
    // Longest name first so "Nam Nguyen" wins over a shorter "Nam" that
    // happens to also be mentioned on the same post/comment.
    const escapedNames = [...validMentions]
      .sort((a, b) => b.name.length - a.name.length)
      .map((mention) => escapeRegExp(mention.name))
      .join('|')
    patternParts.push(`@(?:${escapedNames})(?![\\p{L}\\p{N}])`)
  }

  if (!patternParts.length) {
    return [{ type: 'text', content: props.text }]
  }

  const pattern = new RegExp(patternParts.join('|'), 'giu')
  const result: Segment[] = []
  let lastIndex = 0

  for (const match of props.text.matchAll(pattern)) {
    const matchText = match[0]
    const index = match.index ?? 0

    if (index > lastIndex) {
      result.push({ type: 'text', content: props.text.slice(lastIndex, index) })
    }

    if (matchText.startsWith('#')) {
      result.push({ type: 'hashtag', content: matchText, tag: matchText.slice(1).toLowerCase() })
    } else {
      const name = matchText.slice(1)
      const mention = validMentions.find((candidate) => candidate.name.toLowerCase() === name.toLowerCase())
      result.push(
        mention
          ? { type: 'mention', content: matchText, userId: mention.id }
          : { type: 'text', content: matchText },
      )
    }

    lastIndex = index + matchText.length
  }

  if (lastIndex < props.text.length) {
    result.push({ type: 'text', content: props.text.slice(lastIndex) })
  }

  return result
})
</script>

<template>
  <span>
    <template v-for="(segment, index) in segments" :key="index">
      <router-link
        v-if="segment.type === 'hashtag'"
        :to="{ name: 'hashtag', params: { tag: segment.tag } }"
        class="text-cyber-neon-cyan transition-colors duration-300 hover:underline"
      >{{ segment.content }}</router-link>
      <router-link
        v-else-if="segment.type === 'mention'"
        :to="`/users/${segment.userId}`"
        class="text-cyber-neon-indigo transition-colors duration-300 hover:underline"
      >{{ segment.content }}</router-link>
      <template v-else>{{ segment.content }}</template>
    </template>
  </span>
</template>
