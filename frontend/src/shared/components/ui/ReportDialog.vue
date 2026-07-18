<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AppButton from './AppButton.vue'
import { useReportStore } from '@/modules/report/store/reportStore'
import type { ValidationErrorMeta } from '@/shared/api/types'
import type { ReportableType, ReportReason } from '@/modules/report/types'

const props = defineProps<{ open: boolean; type: ReportableType; id: number }>()
const emit = defineEmits<{ 'update:open': [boolean] }>()

const reportStore = useReportStore()
const { t } = useI18n()

const reasons: ReportReason[] = ['spam', 'harassment', 'inappropriate', 'other']

const reason = ref<ReportReason>('spam')
const details = ref('')
const submitting = ref(false)
const error = ref<string | null>(null)
const submitted = ref(false)

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      reason.value = 'spam'
      details.value = ''
      error.value = null
      submitted.value = false
    }
  },
)

function close(): void {
  emit('update:open', false)
}

async function onSubmit(): Promise<void> {
  submitting.value = true
  error.value = null

  try {
    await reportStore.submitReport(props.type, props.id, reason.value, details.value.trim() || undefined)
    submitted.value = true
  } catch (err) {
    if (axios.isAxiosError(err) && err.response) {
      const body = err.response.data as { error: string | null; meta: ValidationErrorMeta | null }
      const fieldErrors = body.meta?.errors ?? {}
      error.value = fieldErrors.content?.[0] ?? body.error ?? t('report.dialog.genericError')
    } else {
      error.value = t('report.dialog.genericError')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-cyber-bg/80 px-4 backdrop-blur-sm"
      @click="close"
    >
      <div class="w-full max-w-sm rounded-hud border border-cyber-border bg-cyber-glass p-5 backdrop-blur-md" @click.stop>
        <h2 class="font-mono text-xs font-bold uppercase tracking-widest text-cyber-text">{{ t('report.dialog.title') }}</h2>

        <template v-if="submitted">
          <p class="mt-3 font-mono text-xs leading-relaxed text-cyber-neon-cyan">{{ t('report.dialog.success') }}</p>
          <div class="mt-4 flex justify-end">
            <button
              type="button"
              class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
              @click="close"
            >
              {{ t('common.close') }}
            </button>
          </div>
        </template>

        <template v-else>
          <div class="mt-4 space-y-3">
            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ t('report.dialog.reasonLabel') }}
              </label>
              <select
                v-model="reason"
                class="mt-1 block w-full rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              >
                <option v-for="option in reasons" :key="option" :value="option">
                  {{ t(`report.reasons.${option}`) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block font-mono text-[10px] uppercase tracking-widest text-cyber-muted">
                {{ t('report.dialog.detailsLabel') }}
              </label>
              <textarea
                v-model="details"
                rows="3"
                maxlength="500"
                :placeholder="t('report.dialog.detailsPlaceholder')"
                class="mt-1 block w-full resize-none rounded-hud border border-cyber-border bg-cyber-surface/60 px-3 py-2 font-mono text-xs text-cyber-text backdrop-blur-md transition-all duration-300 placeholder:text-cyber-muted focus:border-cyber-neon-cyan/50 focus:outline-none focus:ring-2 focus:ring-cyber-neon-indigo/40"
              />
            </div>
          </div>

          <p v-if="error" class="mt-2 font-mono text-xs text-cyber-neon-pink">{{ error }}</p>

          <div class="mt-4 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-full border border-cyber-border bg-cyber-glass px-4 py-1.5 font-mono text-xs uppercase tracking-wider text-cyber-text backdrop-blur-md transition-all duration-300 hover:border-cyber-neon-cyan/50 hover:shadow-cyan-glow"
              @click="close"
            >
              {{ t('common.cancel') }}
            </button>
            <AppButton :label="t('report.dialog.submit')" :loading="submitting" @click="onSubmit" />
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>
