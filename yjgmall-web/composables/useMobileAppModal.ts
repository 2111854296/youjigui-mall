export function useMobileAppModal() {
  const open = useState('mobile-app-modal-open', () => false)
  const mobileUrl = computed(() => {
    if (import.meta.client) {
      return `${window.location.origin}/h5/#/pages/index/index`
    }
    return 'https://g.aifeili.cn/h5/#/pages/index/index'
  })
  const qrSrc = computed(() =>
    `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=${encodeURIComponent(mobileUrl.value)}`,
  )
  function show() {
    open.value = true
    if (import.meta.client) document.body.style.overflow = 'hidden'
  }
  function hide() {
    open.value = false
    if (import.meta.client) document.body.style.overflow = ''
  }
  return { open, mobileUrl, qrSrc, show, hide }
}
