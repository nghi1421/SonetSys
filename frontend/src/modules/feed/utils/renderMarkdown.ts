import DOMPurify from 'dompurify'
import MarkdownIt from 'markdown-it'
import type { Env, RendererRule, Token } from 'markdown-it'
import type { User } from '../types'

interface MentionEntry {
  id: number
  name: string
}

interface MarkdownEnv extends Env {
  hashtags: string[]
  mentions: MentionEntry[]
}

const ALLOWED_TAGS = [
  'div',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'p',
  'br',
  'hr',
  'strong',
  'em',
  's',
  'del',
  'a',
  'img',
  'ul',
  'ol',
  'li',
  'blockquote',
  'code',
  'pre',
  'table',
  'thead',
  'tbody',
  'tr',
  'th',
  'td',
]

const ALLOWED_ATTR = [
  'href',
  'src',
  'alt',
  'title',
  'target',
  'rel',
  'loading',
  'referrerpolicy',
  'class',
]

// Headings use the neon-indigo accent rather than cyber-text: this renderer
// runs in both ordinary light-theme cards (posts/comments) and the dark
// video-overlay caption on reels, and neon-* tokens are the ones this
// project keeps legible across both, per frontend-standards.md.
const HEADING_CLASSES: Record<string, string> = {
  h1: 'mt-3 mb-1 text-base font-bold text-cyber-neon-indigo',
  h2: 'mt-3 mb-1 text-sm font-bold text-cyber-neon-indigo',
  h3: 'mt-2 mb-1 text-sm font-bold text-cyber-neon-indigo',
  h4: 'mt-2 mb-1 text-xs font-bold text-cyber-neon-indigo',
  h5: 'mt-2 mb-1 text-xs font-bold text-cyber-neon-indigo',
  h6: 'mt-2 mb-1 text-xs font-bold text-cyber-neon-indigo',
}

// markdown-it always calls renderer rules with an idx it guarantees is a
// valid index into tokens — this just gives TypeScript's noUncheckedIndexedAccess
// a typed, fail-loud way to see that instead of a non-null assertion everywhere.
function tokenAt(tokens: Token[], idx: number): Token {
  const token = tokens[idx]
  if (token === undefined) throw new Error(`renderMarkdown: no token at index ${idx}`)
  return token
}

function withClass(cssClass: string): RendererRule {
  return (tokens, idx, options, _env, self) => {
    tokenAt(tokens, idx).attrJoin('class', cssClass)
    return self.renderToken(tokens, idx, options)
  }
}

const md = new MarkdownIt({
  html: false,
  linkify: true,
  // A single newline in a chat-style post should read as a line break, not
  // require the two-trailing-spaces/blank-line CommonMark convention users
  // were never taught (composer ships with no markdown help this round).
  breaks: true,
})

md.renderer.rules.link_open = (tokens, idx, options, _env, self) => {
  const token = tokenAt(tokens, idx)
  token.attrSet('target', '_blank')
  token.attrSet('rel', 'noopener noreferrer')
  token.attrJoin('class', 'text-cyber-neon-indigo hover:underline transition-colors duration-300')
  return self.renderToken(tokens, idx, options)
}

md.renderer.rules.image = (tokens, idx, options, env, self) => {
  const token = tokenAt(tokens, idx)
  // Default markdown-it behavior (not preserved by renderToken()): the
  // image label may itself contain markdown ("![**bold** cat](url)"), so
  // it must be re-rendered as plain text for the alt attribute.
  token.attrSet('alt', self.renderInlineAsText(token.children ?? [], options, env))
  token.attrSet('loading', 'lazy')
  token.attrSet('referrerpolicy', 'no-referrer')
  token.attrJoin('class', 'my-2 max-w-full rounded-hud')
  return self.renderToken(tokens, idx, options)
}

md.renderer.rules.hashtag_link = (tokens, idx) => {
  const token = tokenAt(tokens, idx)
  const tag = String(token.attrGet('data-hashtag'))
  const href = md.utils.escapeHtml(`/hashtag/${tag}`)

  return `<a href="${href}" data-hashtag="${md.utils.escapeHtml(tag)}" class="text-cyber-neon-cyan hover:underline transition-colors duration-300">${md.utils.escapeHtml(token.content)}</a>`
}

md.renderer.rules.mention_link = (tokens, idx) => {
  const token = tokenAt(tokens, idx)
  const userId = String(token.attrGet('data-mention-id'))
  const href = md.utils.escapeHtml(`/users/${userId}`)

  return `<a href="${href}" data-mention-id="${md.utils.escapeHtml(userId)}" class="text-cyber-neon-indigo hover:underline transition-colors duration-300">${md.utils.escapeHtml(token.content)}</a>`
}

md.renderer.rules.heading_open = (tokens, idx, options, _env, self) => {
  const token = tokenAt(tokens, idx)
  token.attrJoin('class', HEADING_CLASSES[token.tag] ?? '')
  return self.renderToken(tokens, idx, options)
}

md.renderer.rules.bullet_list_open = withClass('my-1 ml-4 list-disc space-y-1')
md.renderer.rules.ordered_list_open = withClass('my-1 ml-4 list-decimal space-y-1')
md.renderer.rules.blockquote_open = withClass(
  'my-2 border-l-2 border-cyber-neon-indigo pl-3 italic opacity-80',
)
md.renderer.rules.hr = withClass('my-3 border-cyber-border')
md.renderer.rules.th_open = withClass('border border-cyber-border px-2 py-1 text-left')
md.renderer.rules.td_open = withClass('border border-cyber-border px-2 py-1 text-left')
md.renderer.rules.thead_open = withClass('bg-cyber-glass')
md.renderer.rules.table_open = () =>
  '<div class="my-2 overflow-x-auto"><table class="w-full border-collapse text-xs">'
md.renderer.rules.table_close = () => '</table></div>'

// code_inline/fence keep markdown-it's *default* renderer (it escapes the
// code content specially — a plain withClass()-style override that falls
// back to the generic renderToken() would silently drop the code's content,
// since renderToken() never reads token.content). Instead, a core rule
// stamps the class onto the token beforehand; the default renderer for both
// already includes token.attrs (via renderAttrs()) in its output, fence's
// even preserving that class alongside its own auto-added language-xxx one.
function addClassToTokensOfType(tokens: Token[], type: string, cssClass: string): void {
  for (const token of tokens) {
    if (token.type === type) token.attrJoin('class', cssClass)
    if (token.children) addClassToTokensOfType(token.children, type, cssClass)
  }
}

md.core.ruler.push('feed_markdown_classes', (state) => {
  addClassToTokensOfType(
    state.tokens,
    'code_inline',
    'rounded-hud bg-cyber-glass px-1 py-0.5 font-mono text-[0.7rem] text-cyber-neon-cyan',
  )
  addClassToTokensOfType(
    state.tokens,
    'fence',
    'my-2 block overflow-x-auto rounded-hud border border-cyber-border bg-cyber-surface p-3 font-mono text-[0.7rem] text-cyber-text',
  )
})

function isWordCodePoint(code: number | undefined): boolean {
  return code !== undefined && /[\p{L}\p{N}_]/u.test(String.fromCodePoint(code))
}

function matchHashtagAt(hashtags: string[], rest: string): string | null {
  let best: string | null = null

  for (const tag of hashtags) {
    if (rest.slice(0, tag.length).toLowerCase() !== tag.toLowerCase()) continue
    if (isWordCodePoint(rest.codePointAt(tag.length))) continue
    if (best === null || tag.length > best.length) best = rest.slice(0, tag.length)
  }

  return best
}

function matchMentionAt(mentions: MentionEntry[], rest: string): MentionEntry | null {
  const byLongestName = [...mentions].sort((a, b) => b.name.length - a.name.length)

  for (const mention of byLongestName) {
    if (rest.slice(0, mention.name.length).toLowerCase() !== mention.name.toLowerCase()) continue
    if (isWordCodePoint(rest.codePointAt(mention.name.length))) continue
    return mention
  }

  return null
}

// Only #tag/@Name runs that exactly match this post/comment's own recorded
// hashtags/mentions become links — anything else (a stray "#" or a manually
// typed "@SomeRandomText" that was never picked from the Mention picker)
// renders as plain markdown text. Registered as a real inline rule (not a
// raw-text regex pass) so it naturally skips code spans/fences, matching
// how the backend's HashtagService now ignores hashtags inside code too.
md.inline.ruler.before('text', 'hashtag_mention', (state, silent) => {
  const marker = state.src.charCodeAt(state.pos)
  if (marker !== 0x23 /* # */ && marker !== 0x40 /* @ */) return false

  const env = state.env as MarkdownEnv
  const start = state.pos + 1
  const rest = state.src.slice(start)

  if (marker === 0x23) {
    const tag = matchHashtagAt(env.hashtags ?? [], rest)
    if (tag === null) return false

    if (!silent) {
      const token = state.push('hashtag_link', 'a', 0)
      token.content = `#${tag}`
      token.attrSet('data-hashtag', tag.toLowerCase())
    }
    state.pos = start + tag.length

    return true
  }

  const mention = matchMentionAt(env.mentions ?? [], rest)
  if (mention === null) return false

  if (!silent) {
    const token = state.push('mention_link', 'a', 0)
    token.content = `@${mention.name}`
    token.attrSet('data-mention-id', String(mention.id))
  }
  state.pos = start + mention.name.length

  return true
})

export function renderMarkdown(
  text: string,
  hashtags: readonly string[],
  mentions: readonly User[],
): string {
  const mentionEntries: MentionEntry[] = mentions.filter(
    (mention): mention is User & { id: number; name: string } =>
      mention.id !== null && mention.name !== null,
  )

  const rawHtml = md.render(text, {
    hashtags: [...hashtags],
    mentions: mentionEntries,
  } satisfies MarkdownEnv)

  return DOMPurify.sanitize(rawHtml, { ALLOWED_TAGS, ALLOWED_ATTR })
}
