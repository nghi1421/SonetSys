<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { EllipsisVertical, Heart, MapPin, Megaphone, MessageCircle, Pencil, Trash2 } from '@lucide/vue'
import { useAuthStore } from '@/modules/auth/store/authStore'
import AppButton from '@/shared/components/ui/AppButton.vue'
import ConfirmDialog from '@/shared/components/ui/ConfirmDialog.vue'
import { useRelativeTime } from '@/shared/composables/useRelativeTime'
import CommentThread from './CommentThread.vue'
import LocationMapPreview from './LocationMapPreview.vue'
import PostMedia from './PostMedia.vue'
import ShareMenu from './ShareMenu.vue'
import SharedPostPreview from './SharedPostPreview.vue'
import { useFeedStore } from '../store/feedStore'
import type { Post } from '../types'

const props = withDefaults(
  defineProps<{ post: Post; clickable?: boolean; startWithCommentsOpen?: boolean }>(),
  { clickable: false, startWithCommentsOpen: false },
)

const emit = defineEmits<{ open: [] }>()

const feedStore = useFeedStore()
const authStore = useAuthStore()
const { t } = useI18n()

const showComments = ref(props.startWithCommentsOpen)
const showActionsMenu = ref(false)
const editing = ref(false)
const editBody = ref(props.post.body)
const confirmingDelete = ref(false)

onMounted(async () => {
  if (showComments.value && !feedStore.commentsByPost[props.post.id]) {
    await feedStore.fetchComments(props.post.id)
  }
})

function onOpenDetail(): void {
  if (props.clickable) emit('open')
}

const isOwner = computed(() => authStore.user?.id === props.post.author.id)
const canModerate = computed(
  () => authStore.user?.role.slug === 'admin' || authStore.user?.role.slug === 'moderator',
)
const canDelete = computed(() => isOwner.value || canModerate.value)

async function onToggleLike(): Promise<void> {
  await feedStore.toggleLike(props.post.id)
}

async function onToggleComments(): Promise<void> {
  showComments.value = !showComments.value
  if (showComments.value && !feedStore.commentsByPost[props.post.id]) {
    await feedStore.fetchComments(props.post.id)
  }
}

async function onConfirmDelete(): Promise<void> {
  confirmingDelete.value = false
  await feedStore.deletePost(props.post.id)
}

function startEditing(): void {
  showActionsMenu.value = false
  editBody.value = props.post.body
  editing.value = true
}

function onDeleteClick(): void {
  showActionsMenu.value = false
  confirmingDelete.value = true
}

async function saveEdit(): Promise<void> {
  if (!editBody.value.trim()) return
  await feedStore.updatePost(props.post.id, { body: editBody.value.trim() })
  editing.value = false
}
</script>

<template>
  <article
    class="relative rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow has-[.popover-panel]:z-20"
  >
    <header class="flex items-start justify-between">
      <div>
        <h4 class="text-xs font-bold tracking-wider text-cyber-text">
          //
          <router-link
            v-if="post.author.id"
            :to="`/users/${post.author.id}`"
            class="transition-colors duration-300 hover:text-cyber-neon-cyan"
          >
            {{ post.author.name }}
          </router-link>
          <template v-else>{{ post.author.name }}</template>
        </h4>
        <div class="mt-1 flex items-center gap-2">
          <span class="font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
            {{ useRelativeTime(post.created_at) }}
          </span>
          <span
            v-if="post.is_sponsored"
            class="inline-flex items-center gap-1 rounded-full border border-cyber-neon-indigo/30 bg-cyber-neon-indigo/10 px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-neon-indigo"
          >
            <Megaphone class="h-2.5 w-2.5" />
            {{ t('feed.postCard.sponsored') }}
          </span>
          <span
            v-if="post.location"
            :title="t('feed.postCard.location')"
            class="inline-flex items-center gap-1 rounded-full border border-cyber-neon-cyan/30 bg-cyber-neon-cyan/10 px-2 py-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-neon-cyan"
          >
            <MapPin class="h-2.5 w-2.5" />
            {{ post.location.name }}
          </span>
        </div>
      </div>
      <div v-if="isOwner || canDelete" class="relative">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow"
          :aria-label="t('feed.postCard.actionsLabel')"
          @click="showActionsMenu = !showActionsMenu"
        >
          <EllipsisVertical class="h-4 w-4" />
        </button>

        <div v-if="showActionsMenu" class="fixed inset-0 z-0" @click="showActionsMenu = false" />

        <div
          v-if="showActionsMenu"
          class="popover-panel absolute right-0 z-10 mt-1 w-36 rounded-hud border border-cyber-border bg-cyber-glass py-1 backdrop-blur-md"
          @click.stop
        >
          <button
            v-if="isOwner && !editing"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-text transition-colors duration-300 hover:text-cyber-neon-cyan"
            @click="startEditing"
          >
            <Pencil class="h-3.5 w-3.5" /> {{ t('common.edit') }}
          </button>
          <button
            v-if="canDelete"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-left font-mono text-xs text-cyber-neon-pink transition-colors duration-300 hover:shadow-pink-glow"
            @click="onDeleteClick"
          >
            <Trash2 class="h-3.5 w-3.5" /> {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </header>

    <div v-if="editing" class="mt-3 space-y-2">
      <textarea
        v-model="editBody"
        rows="3"
        class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
      />
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="rounded-full border border-cyber-border bg-cyber-glass px-3 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
          @click="editing = false"
        >
          {{ t('common.cancel') }}
        </button>
        <AppButton :label="t('common.save')" @click="saveEdit" />
      </div>
    </div>
    <template v-else>
      <p
        v-if="post.body"
        class="mt-3 whitespace-pre-wrap border-l border-cyber-neon-indigo pl-2 font-mono text-xs leading-relaxed text-cyber-text/90"
        :class="clickable && 'cursor-pointer'"
        @click="onOpenDetail"
      >
        {{ post.body }}
      </p>
      <PostMedia
        v-if="post.media_type"
        :post="post"
        class="mt-3"
        :class="clickable && 'cursor-pointer'"
        @click="onOpenDetail"
      />
      <SharedPostPreview v-if="post.shared_post" :post="post.shared_post" class="mt-3" />
      <LocationMapPreview
        v-if="post.location"
        :lat="post.location.lat"
        :lng="post.location.lng"
        :name="post.location.name"
        class="mt-3"
      />
    </template>

    <footer class="mt-4 flex items-center gap-2 border-t border-cyber-border pt-3">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-mono text-[10px] transition-all duration-300"
        :class="
          post.liked_by_me
            ? 'border-cyber-neon-pink/40 bg-cyber-neon-pink/10 text-cyber-neon-pink shadow-pink-glow'
            : 'border-cyber-border bg-cyber-glass text-cyber-muted hover:border-cyber-neon-pink/40 hover:text-cyber-neon-pink'
        "
        @click="onToggleLike"
      >
        <Heart class="h-3 w-3" :fill="post.liked_by_me ? 'currentColor' : 'none'" />
        {{ post.likes_count }}
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border border-cyber-border bg-cyber-glass px-2.5 py-1 font-mono text-[10px] text-cyber-muted transition-all duration-300 hover:border-cyber-neon-cyan/40 hover:text-cyber-neon-cyan"
        @click="onToggleComments"
      >
        <MessageCircle class="h-3 w-3" />
        {{ post.comments_count }}
      </button>

      <ShareMenu :post="post" />
    </footer>

    <CommentThread v-if="showComments" :post-id="post.id" />

    <ConfirmDialog
      :open="confirmingDelete"
      :title="t('feed.postCard.confirmDeleteTitle')"
      :message="t('feed.postCard.confirmDeleteMessage')"
      @confirm="onConfirmDelete"
      @cancel="confirmingDelete = false"
    />
  </article>
</template>
