import { defineStore } from 'pinia'
import { reactive, ref } from 'vue'
import { feedApi } from '../api/feedApi'
import type { CreatePostPayload, Post, UpdatePostPayload } from '../types'

export const useFeedStore = defineStore('feed', () => {
  const posts = ref<Post[]>([])
  const commentsByPost = reactive<Record<number, import('../types').Comment[]>>({})
  const nextCursor = ref<string | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const notFound = ref(false)

  async function fetchFeed(): Promise<void> {
    loading.value = true
    try {
      const response = await feedApi.fetchFeed(null)
      posts.value = response.data ?? []
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loading.value = false
    }
  }

  async function fetchMore(): Promise<void> {
    if (!nextCursor.value || loadingMore.value) return
    loadingMore.value = true
    try {
      const response = await feedApi.fetchFeed(nextCursor.value)
      posts.value = [...posts.value, ...(response.data ?? [])]
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loadingMore.value = false
    }
  }

  async function fetchPost(postId: number): Promise<void> {
    loading.value = true
    notFound.value = false
    try {
      const response = await feedApi.fetchPost(postId)
      posts.value = response.data ? [response.data] : []
      notFound.value = !response.data
    } catch {
      posts.value = []
      notFound.value = true
    } finally {
      loading.value = false
    }
  }

  async function createPost(payload: CreatePostPayload): Promise<void> {
    const response = await feedApi.createPost(payload)
    if (response.data) {
      posts.value = [response.data, ...posts.value]
    }
  }

  async function fetchGroupFeed(groupId: number): Promise<void> {
    loading.value = true
    try {
      const response = await feedApi.fetchGroupFeed(groupId, null)
      posts.value = response.data ?? []
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loading.value = false
    }
  }

  async function fetchMoreGroupFeed(groupId: number): Promise<void> {
    if (!nextCursor.value || loadingMore.value) return
    loadingMore.value = true
    try {
      const response = await feedApi.fetchGroupFeed(groupId, nextCursor.value)
      posts.value = [...posts.value, ...(response.data ?? [])]
      nextCursor.value = (response.meta?.next_cursor as string | null) ?? null
    } finally {
      loadingMore.value = false
    }
  }

  async function createGroupPost(groupId: number, payload: CreatePostPayload): Promise<void> {
    const response = await feedApi.createGroupPost(groupId, payload)
    if (response.data) {
      posts.value = [response.data, ...posts.value]
    }
  }

  async function updatePost(postId: number, payload: UpdatePostPayload): Promise<void> {
    const response = await feedApi.updatePost(postId, payload)
    const post = posts.value.find((p) => p.id === postId)
    if (post && response.data) {
      post.body = response.data.body
      post.visibility = response.data.visibility
    }
  }

  async function deletePost(postId: number): Promise<void> {
    await feedApi.deletePost(postId)
    posts.value = posts.value.filter((p) => p.id !== postId)
  }

  async function toggleLike(postId: number): Promise<void> {
    const post = posts.value.find((p) => p.id === postId)
    if (!post) return
    const response = await feedApi.togglePostLike(postId)
    if (response.data) {
      post.liked_by_me = response.data.liked
      post.likes_count = response.data.likes_count
    }
  }

  async function fetchComments(postId: number): Promise<void> {
    const response = await feedApi.fetchComments(postId)
    commentsByPost[postId] = response.data ?? []
  }

  async function createComment(postId: number, body: string, parentId?: number): Promise<void> {
    const response = await feedApi.createComment(postId, { body, parent_id: parentId })
    if (response.data) {
      commentsByPost[postId] = [...(commentsByPost[postId] ?? []), response.data]
      const post = posts.value.find((p) => p.id === postId)
      if (post) post.comments_count += 1
    }
  }

  async function deleteComment(postId: number, commentId: number): Promise<void> {
    await feedApi.deleteComment(commentId)
    commentsByPost[postId] = (commentsByPost[postId] ?? []).filter((c) => c.id !== commentId)
    const post = posts.value.find((p) => p.id === postId)
    if (post) post.comments_count = Math.max(0, post.comments_count - 1)
  }

  async function toggleCommentLike(postId: number, commentId: number): Promise<void> {
    const comment = (commentsByPost[postId] ?? []).find((c) => c.id === commentId)
    if (!comment) return
    const response = await feedApi.toggleCommentLike(commentId)
    if (response.data) {
      comment.liked_by_me = response.data.liked
      comment.likes_count = response.data.likes_count
    }
  }

  return {
    posts,
    commentsByPost,
    nextCursor,
    loading,
    loadingMore,
    notFound,
    fetchFeed,
    fetchMore,
    fetchPost,
    createPost,
    fetchGroupFeed,
    fetchMoreGroupFeed,
    createGroupPost,
    updatePost,
    deletePost,
    toggleLike,
    fetchComments,
    createComment,
    deleteComment,
    toggleCommentLike,
  }
})
