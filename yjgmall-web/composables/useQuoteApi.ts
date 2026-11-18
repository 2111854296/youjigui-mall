type TicketPayload = {
  ticket: string
  ts: number
  sign: string
  expireAt: number
}

type QuoteApiOptions = {
  method?: string
  body?: Record<string, unknown> | FormData | null
  query?: Record<string, string | number | undefined | null>
  auth?: boolean
}

function errCode(err: any): string {
  return String(err?.data?.code || err?.data?.errors?.code?.[0] || '')
}

/**
 * 行情 JSON 专用：短时票据 + 异常时弹滑块。
 * 只影响 /api/v1/quotes*，不挡 HTML 页面收录。
 */
export function useQuoteApi() {
  const config = useRuntimeConfig()
  const { api, token } = useApi()

  const ticketState = useState<TicketPayload | null>('quote-access-ticket', () => null)
  const captchaOpen = useState('quote-captcha-open', () => false)
  const captchaToken = useState<string | null>('quote-captcha-token', () => null)
  const captchaWaiters = useState<Array<(t: string | null) => void>>('quote-captcha-waiters', () => [])

  async function refreshTicket() {
    const res = await $fetch<{ data: { ticket: string; ts: number; sign: string; expire_in: number } }>(
      `${config.public.apiBase}/quotes/ticket`,
    )
    const d = res?.data
    if (!d?.ticket || !d?.sign) {
      throw new Error('无法获取行情访问凭证')
    }
    const ttlMs = Math.max(20, Number(d.expire_in) || 90) * 1000
    ticketState.value = {
      ticket: d.ticket,
      ts: Number(d.ts),
      sign: d.sign,
      expireAt: Date.now() + ttlMs - 8000,
    }
  }

  async function ensureTicket() {
    const cur = ticketState.value
    if (!cur || Date.now() >= cur.expireAt) {
      await refreshTicket()
    }
  }

  function waitCaptcha(): Promise<string | null> {
    if (!import.meta.client) return Promise.resolve(null)
    captchaOpen.value = true
    return new Promise((resolve) => {
      captchaWaiters.value = [...captchaWaiters.value, resolve]
    })
  }

  function resolveCaptcha(token: string | null) {
    captchaOpen.value = false
    if (token) captchaToken.value = token
    const list = captchaWaiters.value
    captchaWaiters.value = []
    list.forEach((fn) => fn(token))
  }

  async function quoteApi<T = any>(path: string, options: QuoteApiOptions = {}, depth = 0): Promise<T> {
    if (depth > 3) throw new Error('行情请求失败，请刷新重试')
    await ensureTicket()
    const t = ticketState.value!
    const headers: Record<string, string> = {
      'X-Quote-Ticket': t.ticket,
      'X-Quote-Ts': String(t.ts),
      'X-Quote-Sign': t.sign,
    }
    if (captchaToken.value) {
      headers['X-Captcha-Token'] = captchaToken.value
      captchaToken.value = null // 一次性
    }

    try {
      return await api<T>(path, { ...options, headers })
    } catch (err: any) {
      const code = errCode(err)
      if (code === 'quote_ticket_required') {
        ticketState.value = null
        await refreshTicket()
        return quoteApi<T>(path, options, depth + 1)
      }
      if (code === 'captcha_required' && import.meta.client) {
        const tok = await waitCaptcha()
        if (!tok) throw err
        captchaToken.value = tok
        return quoteApi<T>(path, options, depth + 1)
      }
      throw err
    }
  }

  return {
    quoteApi,
    token,
    captchaOpen,
    onCaptchaVerified: (tok: string) => resolveCaptcha(tok),
    onCaptchaCancel: () => resolveCaptcha(null),
  }
}
