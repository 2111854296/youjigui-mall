import { buildH5Url, isMobileClient, preferPc, setPreferPc } from '~/utils/mobileGate'

/**
 * 手机 / 窄屏访问 PC 站时自动跳转 H5。
 * ?pc=1 强制留在电脑版；?mobile=1 强制走手机版。
 */
export default defineNuxtPlugin(() => {
  if (!import.meta.client) return

  const path = location.pathname || '/'
  if (path.startsWith('/h5') || path.startsWith('/admin') || path.startsWith('/api')) return

  const qs = new URLSearchParams(location.search)
  if (qs.get('pc') === '1' || qs.get('desktop') === '1') {
    setPreferPc(true)
    return
  }
  if (qs.get('mobile') === '1') {
    setPreferPc(false)
  }
  if (preferPc()) return
  if (!isMobileClient()) return

  const target = buildH5Url(path, location.search)
  if (target && target !== location.href) {
    location.replace(target)
  }
})
