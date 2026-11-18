import { reactive } from 'vue'

export type AppDialogType = 'confirm' | 'alert' | 'prompt'

export type AppDialogOptions = {
  type?: AppDialogType
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  /** danger = 红色确认（删除等） */
  tone?: 'primary' | 'danger' | 'success'
  placeholder?: string
  defaultValue?: string
  /** prompt 是否允许空字符串提交 */
  allowEmpty?: boolean
}

type DialogState = {
  open: boolean
  type: AppDialogType
  title: string
  message: string
  confirmText: string
  cancelText: string
  tone: 'primary' | 'danger' | 'success'
  placeholder: string
  input: string
  allowEmpty: boolean
  resolve: ((value: boolean | string | null) => void) | null
}

const state = reactive<DialogState>({
  open: false,
  type: 'confirm',
  title: '提示',
  message: '',
  confirmText: '确定',
  cancelText: '取消',
  tone: 'primary',
  placeholder: '',
  input: '',
  allowEmpty: true,
  resolve: null,
})

function closeBodyScroll(lock: boolean) {
  if (!import.meta.client) return
  document.body.style.overflow = lock ? 'hidden' : ''
}

function openDialog(opts: AppDialogOptions): Promise<boolean | string | null> {
  if (state.open && state.resolve) {
    state.resolve(state.type === 'prompt' ? null : false)
  }
  const type = opts.type || 'confirm'
  state.type = type
  state.title = opts.title || (type === 'alert' ? '提示' : type === 'prompt' ? '请输入' : '请确认')
  state.message = opts.message || ''
  state.confirmText = opts.confirmText || (type === 'alert' ? '知道了' : '确定')
  state.cancelText = opts.cancelText || '取消'
  state.tone = opts.tone || 'primary'
  state.placeholder = opts.placeholder || ''
  state.input = opts.defaultValue || ''
  state.allowEmpty = opts.allowEmpty !== false
  state.open = true
  closeBodyScroll(true)

  return new Promise((resolve) => {
    state.resolve = resolve
  })
}

function finish(value: boolean | string | null) {
  const r = state.resolve
  state.resolve = null
  state.open = false
  closeBodyScroll(false)
  r?.(value)
}

export function useAppDialog() {
  function confirm(
    messageOrOpts: string | AppDialogOptions,
    maybeTitle?: string,
  ): Promise<boolean> {
    const opts: AppDialogOptions =
      typeof messageOrOpts === 'string'
        ? { type: 'confirm', message: messageOrOpts, title: maybeTitle || '请确认' }
        : { ...messageOrOpts, type: 'confirm' }
    return openDialog(opts) as Promise<boolean>
  }

  function alert(
    messageOrOpts: string | AppDialogOptions,
    maybeTitle?: string,
  ): Promise<void> {
    const opts: AppDialogOptions =
      typeof messageOrOpts === 'string'
        ? { type: 'alert', message: messageOrOpts, title: maybeTitle || '提示' }
        : { ...messageOrOpts, type: 'alert' }
    return openDialog(opts).then(() => undefined)
  }

  function prompt(
    messageOrOpts: string | AppDialogOptions,
    defaultValue = '',
  ): Promise<string | null> {
    const opts: AppDialogOptions =
      typeof messageOrOpts === 'string'
        ? {
            type: 'prompt',
            message: messageOrOpts,
            title: '请输入',
            defaultValue,
            allowEmpty: true,
          }
        : { ...messageOrOpts, type: 'prompt' }
    return openDialog(opts) as Promise<string | null>
  }

  return {
    state,
    confirm,
    alert,
    prompt,
    accept() {
      if (state.type === 'prompt') {
        const v = String(state.input ?? '')
        if (!state.allowEmpty && !v.trim()) return
        finish(v)
        return
      }
      finish(true)
    },
    cancel() {
      finish(state.type === 'prompt' ? null : false)
    },
  }
}

/** 便于非 setup 场景（如富文本） */
export function appConfirm(message: string, title?: string, tone: 'primary' | 'danger' = 'primary') {
  return useAppDialog().confirm({ message, title, tone })
}

export function appAlert(message: string, title?: string) {
  return useAppDialog().alert({ message, title })
}

export function apiErrorText(e: any, fallback = '操作失败'): string {
  const errors = e?.data?.errors
  if (errors && typeof errors === 'object') {
    const text = Object.values(errors).flat().map((v) => String(v || '')).filter(Boolean).join('；')
    if (text) return text
  }
  const message = e?.data?.message || e?.message
  return message ? String(message) : fallback
}

export function appOk(message: string, title = '保存成功') {
  return useAppDialog().alert({ title, message, tone: 'success' })
}

export function appFail(message: string, title = '保存失败') {
  return useAppDialog().alert({ title, message, tone: 'danger' })
}

export function appPrompt(message: string, defaultValue = '', title = '请输入') {
  return useAppDialog().prompt({ message, defaultValue, title, allowEmpty: true })
}

