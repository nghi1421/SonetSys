import { defineStore } from 'pinia'
import { ref } from 'vue'
import { followApi } from '../api/followApi'
import type { FollowUser, UserProfile } from '../types'
import type { Post } from '@/modules/feed/types'

export const useFollowStore = defineStore('follow', () => {
  const profile = ref<UserProfile | null>(null)
  const profileLoading = ref(false)
  const notFound = ref(false)

  const posts = ref<Post[]>([])
  const nextCursor = ref<string | null>(null)
  const postsLoading = ref(false)
  const postsLoadingMore = ref(false)

  const followers = ref<FollowUser[]>([])
  const following = ref<FollowUser[]>([])

  async function fetchProfile(userId: number): Promise<void> {
    profileLoading.value = true
    notFound.value = false
    try {
      const response = await followApi.fetchProfile(userId)
      profile.value = response.data
      notFound.value = !response.data
    } catch {
      profile.value = null
      notFound.value = true
    } finally {
      profileLoading.value = false
    }
  }

  async function fetchProfilePosts(userId: number): Promise<void> {
    postsLoading.value = true
    try {
      const response = await followApi.fetchProfilePosts(userId, null)
      posts.value = response.data ?? []
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      postsLoading.value = false
    }
  }

  async function fetchMoreProfilePosts(userId: number): Promise<void> {
    if (!nextCursor.value || postsLoadingMore.value) return
    postsLoadingMore.value = true
    try {
      const response = await followApi.fetchProfilePosts(userId, nextCursor.value)
      posts.value = [...posts.value, ...(response.data ?? [])]
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      postsLoadingMore.value = false
    }
  }

  async function fetchFollowers(userId: number): Promise<void> {
    const response = await followApi.fetchFollowers(userId)
    followers.value = response.data ?? []
  }

  async function fetchFollowing(userId: number): Promise<void> {
    const response = await followApi.fetchFollowing(userId)
    following.value = response.data ?? []
  }

  function applyFollowState(userId: number, isFollowing: boolean): void {
    if (profile.value?.id === userId) {
      profile.value.is_following = isFollowing
      profile.value.followers_count += isFollowing ? 1 : -1
    }

    for (const user of [...followers.value, ...following.value]) {
      if (user.id === userId) user.is_following = isFollowing
    }
  }

  async function follow(userId: number): Promise<void> {
    await followApi.follow(userId)
    applyFollowState(userId, true)
  }

  async function unfollow(userId: number): Promise<void> {
    await followApi.unfollow(userId)
    applyFollowState(userId, false)
  }

  return {
    profile,
    profileLoading,
    notFound,
    posts,
    nextCursor,
    postsLoading,
    postsLoadingMore,
    followers,
    following,
    fetchProfile,
    fetchProfilePosts,
    fetchMoreProfilePosts,
    fetchFollowers,
    fetchFollowing,
    follow,
    unfollow,
  }
})
