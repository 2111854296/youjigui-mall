import { getToken } from "@/utils/request";
import { useAuth } from "@/composables/useAuth";

/** 未登录则跳转登录，可带回跳路径 */
export function ensureLogin(redirect?: string): boolean {
  if (getToken()) return true;
  try {
    const pages = getCurrentPages();
    const last: any = pages[pages.length - 1];
    if (last?.route === "pages/login/index") return false;
  } catch { /* ignore */ }
  const cur =
    redirect ||
    (() => {
      try {
        const pages = getCurrentPages();
        const last: any = pages[pages.length - 1];
        if (!last) return "";
        const route = "/" + (last.route || "");
        const opts = last.options || {};
        const qs = Object.keys(opts)
          .map((k) => `${k}=${encodeURIComponent(opts[k])}`)
          .join("&");
        return qs ? `${route}?${qs}` : route;
      } catch {
        return "";
      }
    })();
  if (cur) uni.setStorageSync("login_redirect", cur);
  uni.navigateTo({ url: "/pages/login/index" });
  return false;
}

export function consumeLoginRedirect(fallback = "/pages/member/index") {
  const r = uni.getStorageSync("login_redirect");
  uni.removeStorageSync("login_redirect");
  const url = r || fallback;
  if (String(url).startsWith("/pages/member/index") || String(url).includes("/pages/index/") || String(url).includes("/pages/sell/index") || String(url).includes("/pages/buy/index") || String(url).includes("/pages/companies/index")) {
    // tab 页
    const tab = String(url).split("?")[0];
    if (
      [
        "/pages/index/index",
        "/pages/sell/index",
        "/pages/buy/index",
        "/pages/companies/index",
        "/pages/member/index",
      ].includes(tab)
    ) {
      uni.switchTab({ url: tab });
      return;
    }
  }
  uni.redirectTo({ url: String(url).startsWith("/") ? url : `/${url}` });
}

export function errMsg(e: any, fallback = "操作失败") {
  const errors = e?.data?.errors || e?.data?.data?.errors;
  if (errors && typeof errors === "object") {
    const flat = Object.values(errors).flat();
    if (flat.length) return String(flat.join("；"));
  }
  return e?.data?.message || e?.message || fallback;
}

/** 企业会员能力（供应/店铺/广告等） */
export async function ensureEnterprise(opts?: {
  tip?: string;
  redirect?: string;
}): Promise<boolean> {
  if (!ensureLogin(opts?.redirect)) return false;
  const { fetchMe, caps } = useAuth();
  await fetchMe(true);
  const c = caps();
  if (c.supply || c.shop || c.ads) return true;
  uni.showModal({
    title: "需要企业会员",
    content: opts?.tip || "该功能仅对企业会员开放，请先升级为企业会员",
    confirmText: "去了解",
    success: (r) => {
      if (r.confirm) uni.navigateTo({ url: "/pages/member/grade" });
    },
  });
  return false;
}

/** 已认证企业（招聘发布，对齐人才库门控） */
export async function ensureCertifiedEnterprise(opts?: {
  tip?: string;
  redirect?: string;
}): Promise<boolean> {
  if (!ensureLogin(opts?.redirect)) return false;
  const { fetchMe, user } = useAuth();
  const me: any = await fetchMe(true);
  if (me?.is_admin || me?.company_certified || (me?.is_enterprise && me?.is_certified)) {
    return true;
  }
  const needUpgrade = !me?.is_enterprise;
  uni.showModal({
    title: needUpgrade ? "需要企业认证" : "企业尚未认证通过",
    content:
      opts?.tip ||
      (needUpgrade
        ? "招聘发布仅对已认证企业开放，请先升级为企业会员并完成认证"
        : "企业资料审核通过后，才能发布招聘"),
    confirmText: needUpgrade ? "去了解" : "去完善",
    success: (r) => {
      if (!r.confirm) return;
      uni.navigateTo({
        url: needUpgrade ? "/pages/member/grade" : "/pages/member/shop",
      });
    },
  });
  return false;
}
