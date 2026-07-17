<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ArrowDown, ArrowUp, FileText, Pencil, Trash2 } from '@lucide/vue'
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
  <div class="mx-auto max-w-4xl space-y-6">
    <div>
      <h1 class="text-lg font-bold text-slate-900">Menu & Pages</h1>
      <p class="mt-1 text-sm text-slate-500">Manage the site navigation and its static content.</p>
    </div>

    <p
      v-if="actionError"
      class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"
    >
      {{ actionError }}
    </p>

    <!-- Menu items -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <h2 class="border-b border-slate-200 px-4 py-3 text-xs font-bold uppercase tracking-widest text-slate-500">
        Menu Items
      </h2>

      <div v-if="menuStore.loading" class="space-y-2 p-4">
        <div v-for="i in 3" :key="i" class="h-10 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] uppercase tracking-widest text-slate-500">
            <tr>
              <th class="px-4 py-2 font-medium">Label</th>
              <th class="px-4 py-2 font-medium">Slug / Page</th>
              <th class="px-4 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(item, index) in menuStore.items" :key="item.id">
              <td colspan="3" class="p-0">
                <div v-if="editingItemId === item.id" class="space-y-2 p-4">
                  <input
                    v-model="editItemLabel"
                    type="text"
                    maxlength="64"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <select
                    v-model="editItemPageId"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  >
                    <option value="">— No page attached —</option>
                    <option v-for="page in staticPageStore.pages" :key="page.id" :value="String(page.id)">
                      {{ page.title }}
                    </option>
                  </select>
                  <div class="flex gap-2">
                    <button
                      type="button"
                      :disabled="savingItem"
                      class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                      @click="onSaveItem(item)"
                    >
                      Save
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-300"
                      @click="cancelEditItem"
                    >
                      Cancel
                    </button>
                  </div>
                </div>

                <div v-else class="flex items-center justify-between gap-3 px-4 py-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ item.label }}</p>
                    <p class="mt-0.5 text-[11px] uppercase tracking-widest text-slate-400">
                      /{{ item.slug }} · {{ item.static_page ? item.static_page.title : 'no page attached' }}
                    </p>
                  </div>

                  <div class="flex shrink-0 items-center gap-1">
                    <button
                      type="button"
                      :disabled="index === 0"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                      title="Move up"
                      @click="moveItem(index, -1)"
                    >
                      <ArrowUp class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      :disabled="index === menuStore.items.length - 1"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                      title="Move down"
                      @click="moveItem(index, 1)"
                    >
                      <ArrowDown class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-blue-300 hover:text-blue-600"
                      title="Edit"
                      @click="startEditItem(item)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      v-if="!item.is_home"
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-rose-300 hover:text-rose-600"
                      title="Delete"
                      @click="onDeleteItem(item)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-2 border-t border-slate-200 p-4">
        <input
          v-model="newItemLabel"
          type="text"
          maxlength="64"
          placeholder="New menu item label…"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <select
          v-model="newItemPageId"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        >
          <option value="">— No page attached —</option>
          <option v-for="page in staticPageStore.pages" :key="page.id" :value="String(page.id)">
            {{ page.title }}
          </option>
        </select>
        <button
          type="button"
          :disabled="creatingItem || !newItemLabel.trim()"
          class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="onCreateItem"
        >
          Add Menu Item
        </button>
      </div>
    </section>

    <!-- Static pages -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <h2 class="border-b border-slate-200 px-4 py-3 text-xs font-bold uppercase tracking-widest text-slate-500">
        Static Pages
      </h2>

      <div v-if="staticPageStore.loading" class="space-y-2 p-4">
        <div v-for="i in 2" :key="i" class="h-10 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div v-else-if="staticPageStore.pages.length === 0" class="flex flex-col items-center py-10 text-center">
        <FileText class="h-6 w-6 text-slate-300" />
        <p class="mt-2 text-xs text-slate-400">No static pages yet. Create one below.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] uppercase tracking-widest text-slate-500">
            <tr>
              <th class="px-4 py-2 font-medium">Title</th>
              <th class="px-4 py-2 font-medium">Content</th>
              <th class="px-4 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="page in staticPageStore.pages" :key="page.id">
              <td colspan="3" class="p-0">
                <div v-if="editingPageId === page.id" class="space-y-2 p-4">
                  <input
                    v-model="editPageTitle"
                    type="text"
                    maxlength="255"
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <textarea
                    v-model="editPageContent"
                    rows="4"
                    class="block w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
                  />
                  <div class="flex gap-2">
                    <button
                      type="button"
                      :disabled="savingPage"
                      class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                      @click="onSavePage(page)"
                    >
                      Save
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-300"
                      @click="cancelEditPage"
                    >
                      Cancel
                    </button>
                  </div>
                </div>

                <div v-else class="flex items-start justify-between gap-3 px-4 py-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ page.title }}</p>
                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ page.content }}</p>
                  </div>

                  <div class="flex shrink-0 items-center gap-1">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-blue-300 hover:text-blue-600"
                      title="Edit"
                      @click="startEditPage(page)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 p-1.5 text-slate-500 transition-colors duration-200 hover:border-rose-300 hover:text-rose-600"
                      title="Delete"
                      @click="onDeletePage(page)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-2 border-t border-slate-200 p-4">
        <input
          v-model="newPageTitle"
          type="text"
          maxlength="255"
          placeholder="New page title…"
          class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <textarea
          v-model="newPageContent"
          rows="4"
          placeholder="Page content…"
          class="block w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30"
        />
        <button
          type="button"
          :disabled="creatingPage || !newPageTitle.trim() || !newPageContent.trim()"
          class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="onCreatePage"
        >
          Add Static Page
        </button>
      </div>
    </section>
  </div>
</template>
