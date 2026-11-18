type ApiOptions = {
  method?: string
  body?: Record<string, unknown> | FormData | null
  query?: Record<string, string | number | undefined | null>
  auth?: boolean
  headers?: Record<string, string>
  /** 指定用哪套登录。不传时：/admin 接口或当前在后台页面用后台登录，其余用前台登录。 */
  scope?: AuthScope
}

export type AuthScope = 'front' | 'admin'

const COOKIE: Record<AuthScope, string> = {
  front: 'yjgmall_token',
  admin: 'yjgmall_admin_token',
}

const USER_STATE: Record<AuthScope, string> = {
  front: 'auth-user',
  admin: 'auth-admin-user',
}

const cookieOpts = {
  maxAge: 60 * 60 * 24 * 30,
  sameSite: 'lax' as const,
  path: '/',
}

export function useApi(explicit?: AuthScope) {
  const config = useRuntimeConfig()
  const route = useRoute()
  const frontToken = useCookie<string | null>(COOKIE.front, cookieOpts)
  const adminToken = useCookie<string | null>(COOKIE.admin, cookieOpts)

  function resolveScope(path: string, override?: AuthScope): AuthScope {
    if (override) return override
    if (explicit) return explicit
    if (path.startsWith('/admin')) return 'admin'
    return route.path.startsWith('/admin') ? 'admin' : 'front'
  }

  function tokenOf(scope: AuthScope) {
    return scope === 'admin' ? adminToken : frontToken
  }

  async function api<T = any>(path: string, options: ApiOptions = {}): Promise<T> {
    const scope = resolveScope(path, options.scope)
    const token = tokenOf(scope)
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers || {}),
    }
    const withAuth = options.auth !== false && Boolean(token.value)
    if (withAuth) {
      headers.Authorization = `Bearer ${token.value}`
    }
    if (options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
    }

    const query = options.query
      ? Object.fromEntries(
          Object.entries(options.query).filter(([, v]) => v !== undefined && v !== null && v !== ''),
        )
      : undefined

    try {
      return await $fetch<T>(`${config.public.apiBase}${path}`, {
        method: (options.method || 'GET') as any,
        body: options.body || undefined,
        query,
        headers,
      })
    } catch (err: any) {
      const status = err?.statusCode || err?.response?.status
      // 只清当前这套登录，避免后台 401 把前台账号踢掉
      if (status === 401 && withAuth && import.meta.client) {
        token.value = null
        useState(USER_STATE[scope]).value = null
      }
      throw err
    }
  }

  const token = explicit === 'admin' ? adminToken : explicit === 'front' ? frontToken : (route.path.startsWith('/admin') ? adminToken : frontToken)

  return { api, token, frontToken, adminToken, tokenOf }
}
