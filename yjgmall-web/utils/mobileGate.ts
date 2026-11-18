/** PC → H5 路径映射（uni-app hash 路由） */
export function mapPcPathToH5(pathname: string, search = ''): string {
  const path = (pathname || '/').replace(/\/+$/, '') || '/'
  const qs = new URLSearchParams(search.startsWith('?') ? search.slice(1) : search)

  const skip =
    path.startsWith('/h5') ||
    path.startsWith('/admin') ||
    path.startsWith('/api') ||
    path.startsWith('/_nuxt') ||
    path.startsWith('/uploads') ||
    path.startsWith('/file')
  if (skip) return ''

  const idDetail = (page: string, id: string) => `/pages/${page}/detail?id=${encodeURIComponent(id)}`

  if (path === '/' || path === '/index' || path === '/index.html') return '/pages/index/index'
  if (path === '/sell') return '/pages/sell/index'
  if (path === '/buy') return '/pages/buy/index'
  if (path === '/mall') return '/pages/mall/index'
  if (path === '/companies') return '/pages/companies/index'
  if (path === '/news') return '/pages/news/index'
  if (path === '/quotes') return '/pages/quotes/index'
  if (path === '/member' || path === '/member/index') return '/pages/member/index'
  if (path === '/member/grade') return '/pages/member/grade'
  if (path === '/member/cart') return '/pages/member/cart'
  if (path === '/login') return '/pages/login/index'
  if (path === '/register') return '/pages/register/index'
  if (path === '/search') {
    const q = qs.get('q') || qs.get('kw') || ''
    return `/pages/search/index${q ? `?q=${encodeURIComponent(q)}` : ''}`
  }

  let m = path.match(/^\/sell\/([^/]+)$/)
  if (m) return idDetail('sell', m[1])
  m = path.match(/^\/buy\/([^/]+)$/)
  if (m) return idDetail('buy', m[1])
  m = path.match(/^\/mall\/([^/]+)$/)
  if (m) return idDetail('mall', m[1])
  m = path.match(/^\/news\/([^/]+)$/)
  if (m) return idDetail('news', m[1])
  m = path.match(/^\/quotes\/([^/]+)$/)
  if (m) return idDetail('quotes', m[1])
  m = path.match(/^\/companies\/([^/]+)$/)
  if (m) return `/pages/companies/shop?slug=${encodeURIComponent(m[1])}`
  m = path.match(/^\/job\/([^/]+)$/)
  if (m) return idDetail('job', m[1])

  // 其它 PC 页：进 H5 首页，避免卡在不适配页
  return '/pages/index/index'
}

export function isMobileClient(): boolean {
  if (!import.meta.client) return false
  const ua = navigator.userAgent || ''
  const mobileUA = /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Huawei|HarmonyOS|MicroMessenger/i.test(ua)
  const w = Math.min(window.screen?.width || 9999, window.innerWidth || 9999)
  return mobileUA || w < 768
}

export const PREFER_PC_KEY = 'yjg_prefer_pc'

export function preferPc(): boolean {
  if (!import.meta.client) return false
  try {
    return localStorage.getItem(PREFER_PC_KEY) === '1'
  } catch {
    return false
  }
}

export function setPreferPc(on: boolean) {
  if (!import.meta.client) return
  try {
    if (on) localStorage.setItem(PREFER_PC_KEY, '1')
    else localStorage.removeItem(PREFER_PC_KEY)
  } catch { /* ignore */ }
}

export function buildH5Url(pathname?: string, search?: string): string {
  const path = pathname ?? (import.meta.client ? location.pathname : '/')
  const q = search ?? (import.meta.client ? location.search : '')
  const hashPath = mapPcPathToH5(path, q)
  const origin = import.meta.client ? location.origin : 'https://g.aifeili.cn'
  return hashPath ? `${origin}/h5/#${hashPath}` : `${origin}/h5/`
}
