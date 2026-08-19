<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { User } from '../types'
import { renderMarkdown } from '../utils/renderMarkdown'

const props = defineProps<{
  text: string
  hashtags: string[]
  mentions: User[]
}>()

const router = useRouter()

const html = computed(() => renderMarkdown(props.text, props.hashtags, props.mentions))

// Hashtag/mention links are real <a href> tags (so right-click/open-in-new-tab
// still works) but navigation inside the app must go through the router, not
// a full page reload — intercept only those, identified by the data
// attribute renderMarkdown() stamps on them, and let every other click
// (plain text, a real markdown link, a code block, ...) bubble normally.
function onContentClick(event: MouseEvent): void {
  const target = event.target as HTMLElement
  const link = target.closest<HTMLAnchorElement>('a[data-hashtag], a[data-mention-id]')
  if (!link) return

  event.preventDefault()
  event.stopPropagation()

  const href = link.getAttribute('href')
  if (href) router.push(href)
}
</script>

<template>
  <!-- No text color set here on purpose: this renders inside PostCard/
       CommentThread's light-card wrapper AND ReelCard's dark video-overlay
       caption, so color is left to inherit from whichever wrapper the
       caller already colors correctly for its own context. -->
  <div class="markdown-body text-xs leading-relaxed" v-html="html" @click="onContentClick" />
</template>

<style scoped>
.markdown-body :deep(p) {
  margin: 0;
  white-space: pre-wrap;
}
.markdown-body :deep(p + p) {
  margin-top: 0.5rem;
}
.markdown-body :deep(pre) {
  margin: 0;
  white-space: pre-wrap;
}
.markdown-body :deep(> :first-child) {
  margin-top: 0;
}
.markdown-body :deep(> :last-child) {
  margin-bottom: 0;
}
</style>
