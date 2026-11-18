import type { AuthScope } from '~/composables/useApi'

export type MemberCapabilities = {
  shop: boolean
  supply: boolean
  product: boolean
  seller_orders: boolean
  purchase: boolean
  article: boolean
  ads: boolean
  spread: boolean
}

export type AuthUser = {
  id: number
  name: string
  username: string
  email?: string
  mobile?: string
  company_name?: string
  member_type?: string
  is_enterprise?: boolean
  is_personal?: boolean
  capabilities?: MemberCapabilities
  is_admin?: boolean
  is_certified?: boolean
  company_certified?: boolean
  level?: number
  vip_grade_code?: string | null
  vip_expire_at?: string | null
  is_vip?: boolean
  vip_entitlement?: {
    quote_history_days?: number
    grade_name?: string
    code?: string
  } | null
  status?: string
  points?: number
  group_code?: string
  group_label?: string
  avatar?: string
  balance?: string
  deposit?: string
  mobile_verified?: boolean
  bank_verified?: boolean
  truename_verified?: boolean
}

const defaultCaps = (enterprise: boolean): MemberCapabilities => ({
  shop: enterprise,
  supply: enterprise,
  product: enterprise,
  seller_orders: enterprise,
  purchase: true,
  article: enterprise,
  ads: enterprise,
  spread: enterprise,
})

export function useAuth(scope?: AuthScope) {
  const route = useRoute()
  const resolved: AuthScope = scope || (route.path.startsWith('/admin') ? 'admin' : 'front')
  const { api, tokenOf } = useApi(resolved)
  const token = tokenOf(resolved)
  const user = useState<AuthUser | null>(resolved === 'admin' ? 'auth-admin-user' : 'auth-user', () => null)
  const loaded = useState<boolean>(resolved === 'admin' ? 'auth-admin-loaded' : 'auth-loaded', () => false)

  const isEnterprise = computed(() => Boolean(user.value?.is_enterprise))
  const isPersonal = computed(() => Boolean(user.value?.is_personal ?? !user.value?.is_enterprise))
  const caps = computed<MemberCapabilities>(() => {
    if (user.value?.capabilities) return user.value.capabilities
    return defaultCaps(Boolean(user.value?.is_enterprise))
  })

  async function fetchMe(force = false) {
    if (!token.value) {
      user.value = null
      loaded.value = true
      return null
    }
    if (user.value && !force) {
      loaded.value = true
      return user.value
    }
    try {
      const res = await api<{ data: AuthUser }>('/auth/me', { auth: true, scope: resolved })
      user.value = res.data
      loaded.value = true
      return user.value
    } catch (err: any) {
      const status = err?.statusCode || err?.response?.status
      // 只有凭证确实无效才清登录态；网络抖动不要踢下线
      if (status === 401 || status === 403) {
        token.value = null
        user.value = null
      }
      loaded.value = true
      return null
    }
  }

  async function login(payload: { login: string; password: string; captcha_token?: string }) {
    const res = await api<{ data: AuthUser; token: string }>('/auth/login', {
      method: 'POST',
      body: payload,
      auth: false,
      scope: resolved,
    })
    token.value = res.token
    user.value = res.data
    loaded.value = true
    return user.value
  }

  async function register(payload: Record<string, unknown>) {
    const res = await api<{ data: AuthUser; token: string }>('/auth/register', {
      method: 'POST',
      body: payload,
      auth: false,
      scope: resolved,
    })
    token.value = res.token
    user.value = res.data
    loaded.value = true
    return user.value
  }

  async function logout() {
    try {
      if (token.value) {
        await api('/auth/logout', { method: 'POST', scope: resolved })
      }
    } finally {
      token.value = null
      user.value = null
    }
  }

  return { user, loaded, token, fetchMe, login, register, logout, isEnterprise, isPersonal, caps }
}
