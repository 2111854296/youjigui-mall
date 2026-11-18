import { ref } from "vue";
import { API_BASE } from "@/config/env";
import { api, getToken, type ApiOptions } from "@/utils/request";

type TicketPayload = {
  ticket: string;
  ts: number;
  sign: string;
  expireAt: number;
};

let ticketState: TicketPayload | null = null;
let pendingCaptchaToken: string | null = null;
const captchaWaiters: Array<(t: string | null) => void> = [];

/** 行情频控滑块：App 根节点挂载 Modal */
export const quoteCaptchaOpen = ref(false);

function errCode(err: any): string {
  return String(err?.data?.code || err?.data?.errors?.code?.[0] || "");
}

async function refreshTicket() {
  const res = await new Promise<any>((resolve, reject) => {
    uni.request({
      url: `${API_BASE}/quotes/ticket`,
      method: "GET",
      header: { Accept: "application/json" },
      success: (r) => {
        const status = r.statusCode || 0;
        if (status >= 200 && status < 300) resolve(r.data);
        else {
          const err: any = new Error((r.data as any)?.message || `HTTP ${status}`);
          err.statusCode = status;
          err.data = r.data;
          reject(err);
        }
      },
      fail: reject,
    });
  });
  const d = res?.data || res;
  if (!d?.ticket || !d?.sign) throw new Error("无法获取行情访问凭证");
  const ttlMs = Math.max(20, Number(d.expire_in) || 90) * 1000;
  ticketState = {
    ticket: String(d.ticket),
    ts: Number(d.ts),
    sign: String(d.sign),
    expireAt: Date.now() + ttlMs - 8000,
  };
}

async function ensureTicket() {
  if (!ticketState || Date.now() >= ticketState.expireAt) {
    await refreshTicket();
  }
}

function waitCaptcha(): Promise<string | null> {
  quoteCaptchaOpen.value = true;
  return new Promise((resolve) => {
    captchaWaiters.push(resolve);
  });
}

function resolveCaptcha(token: string | null) {
  quoteCaptchaOpen.value = false;
  if (token) pendingCaptchaToken = token;
  const list = captchaWaiters.splice(0, captchaWaiters.length);
  list.forEach((fn) => fn(token));
}

export function onQuoteCaptchaVerified(token: string) {
  resolveCaptcha(token);
}

export function onQuoteCaptchaCancel() {
  resolveCaptcha(null);
}

/**
 * 行情 JSON 专用：短时票据 + 频控滑块。对齐 PC useQuoteApi。
 */
export async function quoteApi<T = any>(
  path: string,
  options: ApiOptions = {},
  depth = 0,
): Promise<T> {
  if (depth > 3) throw new Error("行情请求失败，请刷新重试");
  await ensureTicket();
  const t = ticketState!;
  const headers: Record<string, string> = {
    ...(options.headers || {}),
    "X-Quote-Ticket": t.ticket,
    "X-Quote-Ts": String(t.ts),
    "X-Quote-Sign": t.sign,
  };
  if (pendingCaptchaToken) {
    headers["X-Captcha-Token"] = pendingCaptchaToken;
    pendingCaptchaToken = null;
  }
  try {
    return await api<T>(path, {
      ...options,
      headers,
      auth: options.auth ?? Boolean(getToken()),
    });
  } catch (err: any) {
    const code = errCode(err);
    if (code === "quote_ticket_required") {
      ticketState = null;
      await refreshTicket();
      return quoteApi<T>(path, options, depth + 1);
    }
    if (code === "captcha_required") {
      const tok = await waitCaptcha();
      if (!tok) {
        const e: any = new Error("请完成安全验证后继续");
        e.statusCode = err.statusCode;
        e.data = err.data;
        throw e;
      }
      pendingCaptchaToken = tok;
      return quoteApi<T>(path, options, depth + 1);
    }
    throw err;
  }
}

export function clearQuoteTicket() {
  ticketState = null;
}
