import { API_BASE } from "@/config/env";

const TOKEN_KEY = "yjgmall_token";

export type ApiOptions = {
  method?: "GET" | "POST" | "PUT" | "DELETE" | "PATCH";
  body?: Record<string, unknown> | null;
  query?: Record<string, string | number | boolean | undefined | null>;
  auth?: boolean;
  headers?: Record<string, string>;
};

export function getToken(): string {
  try {
    return uni.getStorageSync(TOKEN_KEY) || "";
  } catch {
    return "";
  }
}

export function setToken(token: string | null) {
  if (token) uni.setStorageSync(TOKEN_KEY, token);
  else uni.removeStorageSync(TOKEN_KEY);
}

function buildQuery(query?: ApiOptions["query"]) {
  if (!query) return "";
  const parts: string[] = [];
  Object.entries(query).forEach(([k, v]) => {
    if (v === undefined || v === null || v === "") return;
    parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
  });
  return parts.length ? `?${parts.join("&")}` : "";
}

export function api<T = any>(path: string, options: ApiOptions = {}): Promise<T> {
  const token = getToken();
  const withAuth = options.auth !== false && Boolean(token);
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers || {}),
  };
  if (withAuth) headers.Authorization = `Bearer ${token}`;
  if (options.body) headers["Content-Type"] = "application/json";

  const url = `${API_BASE}${path}${buildQuery(options.query)}`;

  return new Promise((resolve, reject) => {
    uni.request({
      url,
      method: options.method || "GET",
      data: options.body || undefined,
      header: headers,
      success: (res) => {
        const status = res.statusCode || 0;
        if (status === 401 && withAuth) {
          setToken(null);
        }
        if (status >= 200 && status < 300) {
          resolve(res.data as T);
          return;
        }
        const err: any = new Error((res.data as any)?.message || `HTTP ${status}`);
        err.statusCode = status;
        err.data = res.data;
        reject(err);
      },
      fail: (e) => reject(e),
    });
  });
}

/** 补全相对资源路径到主站；无图/演示商品图默认用站点 logo */
export function mediaUrl(src?: string | null, fallback = "/img/logo-yjg.png") {
  const t = String(src || "").trim();
  if (!t) return fallback;
  if (/\/img\/products\/p\d+\.(jpe?g|png|webp|gif)/i.test(t)) return fallback;
  if (/\/img\/product\.svg/i.test(t)) return fallback;
  if (/placehold|via\.placeholder/i.test(t)) return fallback;
  if (/^https?:\/\//i.test(t) || t.startsWith("data:")) return t;
  if (t.startsWith("//")) return `https:${t}`;
  // uni-app 本地 static，勿拼主站域名
  if (t.startsWith("/static/")) return t;
  if (t.startsWith("/")) return `https://g.aifeili.cn${t}`;
  return `https://g.aifeili.cn/${t}`;
}
