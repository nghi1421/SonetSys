import { defineStore } from 'pinia'
import { ref } from 'vue'
import { blockApi } from '../api/blockApi'
import { followApi } from '../api/followApi'
import type { BlockedUser, FollowUser, UserProfile } from '../types'
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

  const blockedUsers = ref<BlockedUser[]>([])
  const blockedUsersLoading = ref(false)

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

  async function fetchBlockedUsers(): Promise<void> {
    blockedUsersLoading.value = true
    try {
      const response = await blockApi.fetchBlockedUsers()
      blockedUsers.value = response.data ?? []
    } finally {
      blockedUsersLoading.value = false
    }
  }

  async function block(userId: number): Promise<void> {
    await blockApi.block(userId)
    if (profile.value?.id === userId) {
      profile.value.is_blocked = true
      profile.value.is_following = false
      profile.value.is_followed_by = false
    }
  }

  async function unblock(userId: number): Promise<void> {
    await blockApi.unblock(userId)
    if (profile.value?.id === userId) {
      profile.value.is_blocked = false
    }
    blockedUsers.value = blockedUsers.value.filter((user) => user.id !== userId)
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
    blockedUsers,
    blockedUsersLoading,
    fetchProfile,
    fetchProfilePosts,
    fetchMoreProfilePosts,
    fetchFollowers,
    fetchFollowing,
    follow,
    unfollow,
    fetchBlockedUsers,
    block,
    unblock,
  }
})
