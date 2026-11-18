import { api, getToken, setToken } from "@/utils/request";

export type AuthUser = {
  id: number;
  name: string;
  username: string;
  email?: string;
  mobile?: string;
  company_name?: string;
  is_enterprise?: boolean;
  is_personal?: boolean;
  avatar?: string;
  capabilities?: Partial<MemberCapabilities> & Record<string, unknown>;
  [key: string]: unknown;
};

export type MemberCapabilities = {
  shop: boolean;
  supply: boolean;
  product: boolean;
  seller_orders: boolean;
  purchase: boolean;
  article: boolean;
  ads: boolean;
  spread: boolean;
};

function defaultCaps(isEnterprise: boolean): MemberCapabilities {
  return {
    shop: isEnterprise,
    supply: isEnterprise,
    product: isEnterprise,
    seller_orders: isEnterprise,
    purchase: true,
    article: isEnterprise,
    ads: isEnterprise,
    spread: isEnterprise,
  };
}

let cachedUser: AuthUser | null = null;

export function useAuth() {
  function token() {
    return getToken();
  }

  function user() {
    return cachedUser;
  }

  function caps(): MemberCapabilities {
    if (cachedUser?.capabilities) {
      return { ...defaultCaps(!!cachedUser.is_enterprise), ...(cachedUser.capabilities as Partial<MemberCapabilities>) };
    }
    return defaultCaps(!!cachedUser?.is_enterprise);
  }

  async function fetchMe(force = false) {
    if (!getToken()) {
      cachedUser = null;
      return null;
    }
    if (cachedUser && !force) return cachedUser;
    try {
      const res = await api<{ data: AuthUser }>("/auth/me", { auth: true });
      cachedUser = res.data;
      return cachedUser;
    } catch {
      cachedUser = null;
      return null;
    }
  }

  async function login(payload: { login: string; password: string; captcha_token?: string }) {
    const res = await api<{ data: AuthUser; token: string }>("/auth/login", {
      method: "POST",
      body: payload,
      auth: false,
    });
    setToken(res.token);
    cachedUser = res.data;
    return cachedUser;
  }

  async function logout() {
    try {
      if (getToken()) await api("/auth/logout", { method: "POST" });
    } finally {
      setToken(null);
      cachedUser = null;
    }
  }

  async function addFavorite(type: "supply" | "purchase" | "product", id: number | string) {
    return api("/member/favorites", {
      method: "POST",
      body: { type, id: Number(id) },
    });
  }

  return { token, fetchMe, login, logout, user, addFavorite, caps };
}
