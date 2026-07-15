<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ArrowDown, ArrowUp, FileText, Pencil, Trash2 } from '@lucide/vue'
import AppShell from '@/shared/components/layout/AppShell.vue'
import AppButton from '@/shared/components/ui/AppButton.vue'
import { useMenuStore } from '../store/menuStore'
import { useStaticPageStore } from '../store/staticPageStore'
import type { MenuItem, StaticPage } from '../types'

const menuStore = useMenuStore()
const staticPageStore = useStaticPageStore()

const actionError = ref<string | null>(null)

const newItemLabel = ref('')
const newItemPageId = ref<string>('')
const creatingItem = ref(false)

const editingItemId = ref<number | null>(null)
const editItemLabel = ref('')
const editItemPageId = ref<string>('')
const savingItem = ref(false)

const newPageTitle = ref('')
const newPageContent = ref('')
const creatingPage = ref(false)

const editingPageId = ref<number | null>(null)
const editPageTitle = ref('')
const editPageContent = ref('')
const savingPage = ref(false)

onMounted(() => {
  menuStore.fetchMenu()
  staticPageStore.fetchPages()
})

async function withErrorHandling(action: () => Promise<void>): Promise<void> {
  actionError.value = null
  try {
    await action()
  } catch {
    actionError.value = 'Something went wrong. Please try again.'
  }
}

async function onCreateItem(): Promise<void> {
  if (!newItemLabel.value.trim()) return
  creatingItem.value = true
  await withErrorHandling(async () => {
    await menuStore.createItem({
      label: newItemLabel.value.trim(),
      static_page_id: newItemPageId.value ? Number(newItemPageId.value) : null,
    })
    newItemLabel.value = ''
    newItemPageId.value = ''
  })
  creatingItem.value = false
}

function startEditItem(item: MenuItem): void {
  editingItemId.value = item.id
  editItemLabel.value = item.label
  editItemPageId.value = item.static_page ? String(item.static_page.id) : ''
}

function cancelEditItem(): void {
  editingItemId.value = null
}

async function onSaveItem(item: MenuItem): Promise<void> {
  if (!editItemLabel.value.trim()) return
  savingItem.value = true
  await withErrorHandling(async () => {
    await menuStore.updateItem(item.id, {
      label: editItemLabel.value.trim(),
      static_page_id: editItemPageId.value ? Number(editItemPageId.value) : null,
    })
    editingItemId.value = null
  })
  savingItem.value = false
}

async function onDeleteItem(item: MenuItem): Promise<void> {
  await withErrorHandling(() => menuStore.removeItem(item.id))
}

async function moveItem(index: number, direction: -1 | 1): Promise<void> {
  const items = menuStore.items
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= items.length) return

  const reordered = [...items]
  const temp = reordered[index]!
  reordered[index] = reordered[targetIndex]!
  reordered[targetIndex] = temp

  await withErrorHandling(() => menuStore.reorder(reordered.map((i) => i.id)))
}

async function onCreatePage(): Promise<void> {
  if (!newPageTitle.value.trim() || !newPageContent.value.trim()) return
  creatingPage.value = true
  await withErrorHandling(async () => {
    await staticPageStore.createPage({ title: newPageTitle.value.trim(), content: newPageContent.value })
    newPageTitle.value = ''
    newPageContent.value = ''
  })
  creatingPage.value = false
}

function startEditPage(page: StaticPage): void {
  editingPageId.value = page.id
  editPageTitle.value = page.title
  editPageContent.value = page.content
}

function cancelEditPage(): void {
  editingPageId.value = null
}

async function onSavePage(page: StaticPage): Promise<void> {
  if (!editPageTitle.value.trim() || !editPageContent.value.trim()) return
  savingPage.value = true
  await withErrorHandling(async () => {
    await staticPageStore.updatePage(page.id, { title: editPageTitle.value.trim(), content: editPageContent.value })
    editingPageId.value = null
  })
  savingPage.value = false
}

async function onDeletePage(page: StaticPage): Promise<void> {
  await withErrorHandling(() => staticPageStore.removePage(page.id))
}
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-2xl space-y-6">
      <h1 class="text-sm font-bold tracking-wider text-cyber-text">// Manage Menu</h1>

      <p
        v-if="actionError"
        class="rounded-hud border border-cyber-neon-pink/30 bg-cyber-neon-pink/10 p-3 font-mono text-xs text-cyber-neon-pink"
      >
        {{ actionError }}
      </p>

      <!-- Menu items -->
      <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">Menu Items</h2>

        <div v-if="menuStore.loading" class="mt-4 space-y-2">
          <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded-hud bg-cyber-surface/60" />
        </div>

        <ul v-else class="mt-4 divide-y divide-cyber-border">
          <li v-for="(item, index) in menuStore.items" :key="item.id" class="py-3">
            <div v-if="editingItemId === item.id" class="space-y-2">
              <input
                v-model="editItemLabel"
                type="text"
                maxlength="64"
                class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
              <select
                v-model="editItemPageId"
                class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              >
                <option value="">— No page attached —</option>
                <option v-for="page in staticPageStore.pages" :key="page.id" :value="String(page.id)">
                  {{ page.title }}
                </option>
              </select>
              <div class="flex gap-2">
                <AppButton label="Save" :loading="savingItem" @click="onSaveItem(item)" />
                <AppButton label="Cancel" variant="secondary" @click="cancelEditItem" />
              </div>
            </div>

            <div v-else class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-xs font-bold text-cyber-text">{{ item.label }}</p>
                <p class="mt-0.5 font-mono text-[9px] uppercase tracking-widest text-cyber-muted">
                  /{{ item.slug }} · {{ item.static_page ? item.static_page.title : 'no page attached' }}
                </p>
              </div>

              <div class="flex shrink-0 items-center gap-1">
                <button
                  type="button"
                  :disabled="index === 0"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
                  title="Move up"
                  @click="moveItem(index, -1)"
                >
                  <ArrowUp class="h-3.5 w-3.5" />
                </button>
                <button
                  type="button"
                  :disabled="index === menuStore.items.length - 1"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:text-cyber-neon-cyan hover:shadow-cyan-glow disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-none"
                  title="Move down"
                  @click="moveItem(index, 1)"
                >
                  <ArrowDown class="h-3.5 w-3.5" />
                </button>
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/50 hover:text-cyber-neon-indigo hover:shadow-cyan-glow"
                  title="Edit"
                  @click="startEditItem(item)"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button
                  v-if="!item.is_home"
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
                  title="Delete"
                  @click="onDeleteItem(item)"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </li>
        </ul>

        <div class="mt-4 space-y-2 border-t border-cyber-border pt-4">
          <input
            v-model="newItemLabel"
            type="text"
            maxlength="64"
            placeholder="New menu item label…"
            class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          />
          <select
            v-model="newItemPageId"
            class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          >
            <option value="">— No page attached —</option>
            <option v-for="page in staticPageStore.pages" :key="page.id" :value="String(page.id)">
              {{ page.title }}
            </option>
          </select>
          <AppButton
            label="Add Menu Item"
            :loading="creatingItem"
            :disabled="!newItemLabel.trim()"
            @click="onCreateItem"
          />
        </div>
      </section>

      <!-- Static pages -->
      <section class="rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md">
        <h2 class="text-xs font-bold uppercase tracking-widest text-cyber-neon-cyan">Static Pages</h2>

        <div v-if="staticPageStore.loading" class="mt-4 space-y-2">
          <div v-for="i in 2" :key="i" class="h-10 animate-pulse rounded-hud bg-cyber-surface/60" />
        </div>

        <div
          v-else-if="staticPageStore.pages.length === 0"
          class="mt-4 flex flex-col items-center py-8 text-center"
        >
          <FileText class="h-6 w-6 text-cyber-muted" />
          <p class="mt-2 font-mono text-xs text-cyber-muted">No static pages yet. Create one below.</p>
        </div>

        <ul v-else class="mt-4 divide-y divide-cyber-border">
          <li v-for="page in staticPageStore.pages" :key="page.id" class="py-3">
            <div v-if="editingPageId === page.id" class="space-y-2">
              <input
                v-model="editPageTitle"
                type="text"
                maxlength="255"
                class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
              <textarea
                v-model="editPageContent"
                rows="4"
                class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
              <div class="flex gap-2">
                <AppButton label="Save" :loading="savingPage" @click="onSavePage(page)" />
                <AppButton label="Cancel" variant="secondary" @click="cancelEditPage" />
              </div>
            </div>

            <div v-else class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-xs font-bold text-cyber-text">{{ page.title }}</p>
                <p class="mt-1 line-clamp-2 font-mono text-xs text-cyber-muted">{{ page.content }}</p>
              </div>

              <div class="flex shrink-0 items-center gap-1">
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-indigo/50 hover:text-cyber-neon-indigo hover:shadow-cyan-glow"
                  title="Edit"
                  @click="startEditPage(page)"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button
                  type="button"
                  class="rounded-full border border-cyber-border bg-cyber-glass p-1.5 text-cyber-muted backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-pink/50 hover:text-cyber-neon-pink hover:shadow-pink-glow"
                  title="Delete"
                  @click="onDeletePage(page)"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </li>
        </ul>

        <div class="mt-4 space-y-2 border-t border-cyber-border pt-4">
          <input
            v-model="newPageTitle"
            type="text"
            maxlength="255"
            placeholder="New page title…"
            class="block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          />
          <textarea
            v-model="newPageContent"
            rows="4"
            placeholder="Page content…"
            class="block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
          />
          <AppButton
            label="Add Static Page"
            :loading="creatingPage"
            :disabled="!newPageTitle.trim() || !newPageContent.trim()"
            @click="onCreatePage"
          />
        </div>
      </section>
    </div>
  </AppShell>
</template>
