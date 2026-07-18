<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useReportStore } from '../store/reportStore'
import type { AdminReport } from '../types'

const reportStore = useReportStore()
const { t } = useI18n()
const page = ref(1)
const loadError = ref<string | null>(null)
const rowError = ref<string | null>(null)
const actingId = ref<number | null>(null)

const totalPages = computed(() =>
  reportStore.adminMeta ? Math.max(1, Math.ceil(reportStore.adminMeta.total / reportStore.adminMeta.per_page)) : 1,
)

onMounted(load)

async function load(): Promise<void> {
  loadError.value = null
  try {
    await reportStore.fetchAdminQueue(page.value)
  } catch {
    loadError.value = t('admin.reports.loadError')
  }
}

function goToPage(next: number): void {
  page.value = next
  load()
}

function contentTypeLabel(report: AdminReport): string {
  return report.reportable_type === 'post' ? t('admin.reports.typePost') : t('admin.reports.typeComment')
}

function reasonLabel(report: AdminReport): string {
  return t(`report.reasons.${report.reason}`)
}

async function onResolve(report: AdminReport): Promise<void> {
  rowError.value = null
  actingId.value = report.id
  try {
    await reportStore.resolveReport(report.id)
  } catch (err) {
    rowError.value = extractError(err, t('admin.reports.resolveError'))
  } finally {
    actingId.value = null
  }
}

async function onDismiss(report: AdminReport): Promise<void> {
  rowError.value = null
  actingId.value = report.id
  try {
    await reportStore.dismissReport(report.id)
  } catch (err) {
    rowError.value = extractError(err, t('admin.reports.dismissError'))
  } finally {
    actingId.value = null
  }
}

function extractError(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err) && err.response) {
    const body = err.response.data as { error: string | null }
    return body.error ?? fallback
  }
  return fallback
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-4">
    <div>
      <h1 class="text-lg font-bold text-slate-900">{{ t('admin.reports.title') }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('admin.reports.subtitle') }}</p>
    </div>

    <p v-if="loadError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ loadError }}</p>
    <p v-if="rowError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ rowError }}</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-widest text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('admin.reports.reporterHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.reports.contentHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.reports.reasonHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.reports.reportedHeader') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('admin.reports.actionsHeader') }}</th>
          </tr>
        </thead>
        <tbody v-if="reportStore.adminLoading" class="divide-y divide-slate-100">
          <tr v-for="i in 5" :key="i">
            <td colspan="5" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="reportStore.adminReports.length === 0">
          <tr>
            <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">{{ t('admin.reports.emptyState') }}</td>
          </tr>
        </tbody>
        <tbody v-else class="divide-y divide-slate-100">
          <tr
            v-for="report in reportStore.adminReports"
            :key="report.id"
            class="transition-colors duration-150 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ report.reporter.name ?? '—' }}
            </td>
            <td class="px-4 py-3 text-slate-600">
              <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5 text-[10px] uppercase tracking-widest text-slate-500">
                {{ contentTypeLabel(report) }}
              </span>
              <div class="mt-1">
                {{ report.excerpt ?? t('admin.reports.contentUnavailable') }}
              </div>
              <router-link
                v-if="report.post_id"
                :to="`/posts/${report.post_id}`"
                class="text-xs text-blue-600 hover:underline"
              >
                {{ t('admin.reports.viewPostLink') }}
              </router-link>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ reasonLabel(report) }}</td>
            <td class="px-4 py-3 text-slate-500">{{ new Date(report.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button
                  type="button"
                  :disabled="actingId === report.id"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-rose-300 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="onResolve(report)"
                >
                  {{ t('admin.reports.resolveButton') }}
                </button>
                <button
                  type="button"
                  :disabled="actingId === report.id"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="onDismiss(report)"
                >
                  {{ t('admin.reports.dismissButton') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="reportStore.adminMeta && totalPages > 1" class="flex items-center justify-between">
      <button
        type="button"
        :disabled="page <= 1"
        class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        @click="goToPage(page - 1)"
      >
        {{ t('common.previous') }}
      </button>
      <span class="text-[11px] tabular-nums text-slate-400">Page {{ page }} / {{ totalPages }}</span>
      <button
        type="button"
        :disabled="page >= totalPages"
        class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        @click="goToPage(page + 1)"
      >
        {{ t('common.next') }}
      </button>
    </div>
  </div>
</template>
