/**
 * 行情卡：收藏 / 分享
 * - 收藏：登录后写入 /member/favorites，type=quote
 * - 分享：系统分享或复制链接
 */
export function useQuoteCardActions() {
  const { api, token } = useApi()
  const { user, fetchMe } = useAuth()
  const route = useRoute()

  const favBusy = ref(false)
  const actionTip = ref('')
  let tipTimer: ReturnType<typeof setTimeout> | null = null

  function showTip(msg: string) {
    actionTip.value = msg
    if (tipTimer) clearTimeout(tipTimer)
    tipTimer = setTimeout(() => {
      actionTip.value = ''
    }, 2200)
  }

  function quoteUrl(id?: number | string | null) {
    if (import.meta.client) {
      if (id != null && id !== '') return `${window.location.origin}/quotes/${id}`
      return window.location.href
    }
    if (id != null && id !== '') return `/quotes/${id}`
    return String(route.fullPath || '/quotes')
  }

  async function shareQuote(p: { id?: number | string | null; name?: string } | null | undefined) {
    if (!p) return
    const url = quoteUrl(p.id)
    const title = p.name ? `${p.name} · 有机硅行情` : '有机硅行情'
    const text = p.name ? `${p.name} 最新参考价` : '最新参考价'
    const payload = `${title}\n${url}`

    // PC 端 Web Share 经常“成功”却看不见面板，优先复制链接
    const canHover = typeof window !== 'undefined' && window.matchMedia('(hover: hover) and (pointer: fine)').matches
    const isMobileShare = !canHover
      && typeof navigator !== 'undefined'
      && typeof navigator.share === 'function'

    if (isMobileShare) {
      try {
        await navigator.share({ title, text, url })
        showTip('分享完成')
        return
      } catch (e: any) {
        // 用户取消不提示；其它错误再走复制
        if (e?.name === 'AbortError') return
      }
    }

    try {
      await navigator.clipboard.writeText(payload)
      showTip('链接已复制，可粘贴分享')
    } catch {
      showTip('复制失败，请手动复制地址栏')
    }
  }

  async function favoriteQuote(p: { id?: number | string | null; name?: string } | null | undefined) {
    if (!p) return
    const id = Number(p.id)
    if (!Number.isFinite(id) || id <= 0) {
      showTip('该品种暂不支持收藏')
      return
    }
    if (!token.value) {
      showTip('请先登录后再收藏')
      await navigateTo(`/login?redirect=${encodeURIComponent(quoteUrl(id))}`)
      return
    }
    if (favBusy.value) return
    favBusy.value = true
    try {
      await fetchMe()
      await api('/member/favorites', {
        method: 'POST',
        body: { type: 'quote', id },
      })
      showTip('已收藏，可在「我的收藏」查看')
    } catch (e: any) {
      const status = e?.statusCode || e?.status || e?.response?.status
      if (status === 401) {
        showTip('请先登录后再收藏')
        await navigateTo(`/login?redirect=${encodeURIComponent(quoteUrl(id))}`)
      } else {
        showTip(e?.data?.message || e?.message || '收藏失败，请稍后重试')
      }
    } finally {
      favBusy.value = false
    }
  }

  return {
    actionTip,
    favBusy,
    shareQuote,
    favoriteQuote,
    user,
  }
}
