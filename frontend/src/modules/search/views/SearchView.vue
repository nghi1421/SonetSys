<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Hash, Inbox, Search, User, Users } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import { useSearchStore } from '../store/searchStore'

const SEARCH_DEBOUNCE_MS = 400
const MIN_QUERY_LENGTH = 2

const searchStore = useSearchStore()
const { t } = useI18n()

const inputQuery = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | undefined

const isEmpty = computed(
  () =>
    searchStore.results.posts.length === 0 &&
    searchStore.results.users.length === 0 &&
    searchStore.results.groups.length === 0 &&
    searchStore.results.hashtags.length === 0,
)

function onSearchInput(): void {
  if (debounceTimer) clearTimeout(debounceTimer)

  const term = inputQuery.value.trim()
  if (term.length < MIN_QUERY_LENGTH) {
    searchStore.reset()
    return
  }

  debounceTimer = setTimeout(() => {
    searchStore.search(term)
  }, SEARCH_DEBOUNCE_MS)
}

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="rounded-hud border border-cyber-border bg-cyber-glass p-4 backdrop-blur-md">
        <div class="flex items-center gap-2 rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2">
          <Search class="h-4 w-4 shrink-0 text-cyber-neon-cyan" />
          <input
            v-model="inputQuery"
            type="text"
            :placeholder="t('search.searchView.placeholder')"
            class="w-full bg-transparent font-mono text-xs text-cyber-text placeholder:text-cyber-muted focus:outline-none"
            @input="onSearchInput"
          />
        </div>
      </header>

      <div v-if="!searchStore.searched && !searchStore.loading" class="flex flex-col items-center py-16 text-center">
        <Search class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('search.searchView.promptTitle') }}</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('search.searchView.promptDescription') }}</p>
      </div>

      <div v-else-if="searchStore.loading" class="space-y-4">
        <div
          v-for="i in 3"
          :key="i"
          class="animate-pulse rounded-hud border border-cyber-border bg-cyber-surface/60 p-4 backdrop-blur-md"
        >
          <div class="h-3 w-32 rounded-full bg-cyber-border" />
          <div class="mt-3 h-2.5 w-full rounded-full bg-cyber-border" />
          <div class="mt-2 h-2.5 w-2/3 rounded-full bg-cyber-border" />
        </div>
      </div>

      <div
        v-else-if="searchStore.error"
        class="rounded-hud border border-cyber-neon-pink/40 bg-cyber-neon-pink/10 p-6 text-center"
      >
        <p class="font-mono text-xs text-cyber-neon-pink">{{ t('search.searchView.error') }}</p>
      </div>

      <div v-else-if="isEmpty" class="flex flex-col items-center py-16 text-center">
        <Inbox class="h-8 w-8 text-cyber-muted" />
        <p class="mt-4 text-xs font-bold text-cyber-text">{{ t('search.searchView.emptyTitle') }}</p>
        <p class="mt-1 font-mono text-xs text-cyber-muted">{{ t('search.searchView.emptyDescription') }}</p>
      </div>

      <template v-else>
        <section v-if="searchStore.results.posts.length" class="space-y-2">
          <h2 class="font-mono text-[10px] uppercase tracking-widest text-cyber-neon-cyan">
            {{ t('search.searchView.sections.posts') }}
          </h2>
          <router-link
            v-for="post in searchStore.results.posts"
            :key="`post-${post.id}`"
            :to="{ name: 'post-detail', params: { id: post.id } }"
            class="block rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          >
            <p class="font-mono text-[9px] uppercase tracking-widest text-cyber-muted">{{ post.author.name }}</p>
            <p class="mt-1 line-clamp-2 font-mono text-xs leading-relaxed text-cyber-text/90">{{ post.body }}</p>
          </router-link>
        </section>

        <section v-if="searchStore.results.users.length" class="space-y-2">
          <h2 class="font-mono text-[10px] uppercase tracking-widest text-cyber-neon-indigo">
            {{ t('search.searchView.sections.people') }}
          </h2>
          <router-link
            v-for="user in searchStore.results.users"
            :key="`user-${user.id}`"
            :to="{ name: 'user-profile', params: { id: user.id } }"
            class="flex items-center gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          >
            <img
              v-if="user.avatar_url"
              :src="user.avatar_url"
              :alt="user.name"
              class="h-8 w-8 rounded-full object-cover"
            />
            <div
              v-else
              class="flex h-8 w-8 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface text-cyber-muted"
            >
              <User class="h-4 w-4" />
            </div>
            <span class="font-mono text-xs text-cyber-text">{{ user.name }}</span>
          </router-link>
        </section>

        <section v-if="searchStore.results.groups.length" class="space-y-2">
          <h2 class="font-mono text-[10px] uppercase tracking-widest text-cyber-neon-pink">
            {{ t('search.searchView.sections.groups') }}
          </h2>
          <router-link
            v-for="group in searchStore.results.groups"
            :key="`group-${group.id}`"
            :to="{ name: 'group-detail', params: { slug: group.slug } }"
            class="flex items-center gap-3 rounded-hud border border-cyber-border bg-cyber-glass p-3 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          >
            <img
              v-if="group.avatar_url"
              :src="group.avatar_url"
              :alt="group.name"
              class="h-8 w-8 rounded-full object-cover"
            />
            <div
              v-else
              class="flex h-8 w-8 items-center justify-center rounded-full border border-cyber-border bg-cyber-surface text-cyber-muted"
            >
              <Users class="h-4 w-4" />
            </div>
            <span class="font-mono text-xs text-cyber-text">{{ group.name }}</span>
          </router-link>
        </section>

        <section v-if="searchStore.results.hashtags.length" class="space-y-2">
          <h2 class="font-mono text-[10px] uppercase tracking-widest text-cyber-neon-cyan">
            {{ t('search.searchView.sections.hashtags') }}
          </h2>
          <div class="flex flex-wrap gap-2">
            <router-link
              v-for="tag in searchStore.results.hashtags"
              :key="`hashtag-${tag}`"
              :to="{ name: 'hashtag', params: { tag } }"
              class="inline-flex items-center gap-1 rounded-full border border-cyber-border bg-cyber-glass px-3 py-1.5 font-mono text-xs text-cyber-neon-cyan backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
            >
              <Hash class="h-3 w-3" />
              {{ tag }}
            </router-link>
          </div>
        </section>
      </template>
    </div>
  </AppShell>
</template>
