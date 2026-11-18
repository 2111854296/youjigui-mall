/** PC 站内路径 → H5 pages 路径（banner / 公告 link） */
const TAB_PAGES = new Set([
  "/pages/index/index",
  "/pages/sell/index",
  "/pages/buy/index",
  "/pages/companies/index",
  "/pages/member/index",
]);

export function normalizeAdLink(link?: string | null) {
  const s = String(link || "").trim();
  if (!s || s === "#" || s === "###" || s.toLowerCase().startsWith("javascript:")) return "";
  return s;
}

export function isInternalAdLink(link?: string | null) {
  const s = normalizeAdLink(link);
  return s.startsWith("/") && !s.startsWith("//");
}

export function isExternalAdLink(link?: string | null) {
  const s = normalizeAdLink(link);
  return /^https?:\/\//i.test(s);
}

/** 将 PC/绝对站内路径映射为 H5 路由 */
export function mapPcPathToH5(link: string): string {
  const raw = normalizeAdLink(link);
  if (!raw) return "";
  if (raw.startsWith("/pages/")) return raw;

  const path = raw.split("?")[0];
  const qs = raw.includes("?") ? raw.slice(raw.indexOf("?")) : "";

  const detail = (base: string, id: string) => `/pages/${base}/detail?id=${encodeURIComponent(id)}`;

  let m: RegExpMatchArray | null;
  if ((m = path.match(/^\/sell\/(\d+)/))) return detail("sell", m[1]);
  if ((m = path.match(/^\/buy\/(\d+)/))) return detail("buy", m[1]);
  if ((m = path.match(/^\/mall\/(\d+)/))) return detail("mall", m[1]);
  if ((m = path.match(/^\/news\/(\d+)/))) return detail("news", m[1]);
  if ((m = path.match(/^\/companies\/([^/]+)/))) {
    return `/pages/companies/shop?slug=${encodeURIComponent(m[1])}`;
  }
  if ((m = path.match(/^\/job\/(\d+)/))) return `/pages/job/detail?id=${encodeURIComponent(m[1])}`;
  if ((m = path.match(/^\/know\/(\d+)/))) return `/pages/know/detail?id=${encodeURIComponent(m[1])}`;

  const map: Record<string, string> = {
    "/": "/pages/index/index",
    "/sell": "/pages/sell/index",
    "/buy": "/pages/buy/index",
    "/companies": "/pages/companies/index",
    "/mall": "/pages/mall/index",
    "/news": "/pages/news/index",
    "/quotes": "/pages/quotes/index",
    "/announce": "/pages/announce/index",
    "/link": "/pages/link/index",
    "/guestbook": "/pages/guestbook/index",
    "/ask": "/pages/ask/index",
    "/search": "/pages/search/index",
    "/category": "/pages/category/index",
    "/pages/help": "/pages/cms/page?slug=help",
    "/pages/contact": "/pages/cms/page?slug=contact",
    "/pages/about": "/pages/cms/page?slug=about",
    "/pages/vip": "/pages/cms/page?slug=vip",
    "/pages/safety": "/pages/cms/page?slug=safety",
    "/help": "/pages/cms/page?slug=help",
    "/contact": "/pages/cms/page?slug=contact",
    "/about": "/pages/cms/page?slug=about",
    "/vip": "/pages/cms/page?slug=vip",
    "/safety": "/pages/cms/page?slug=safety",
  };

  if (map[path]) {
    const target = map[path];
    return target.includes("?") ? target : `${target}${qs}`;
  }

  // 已是 H5 形态或未知：原样返回（含 query）
  return raw.startsWith("/") ? raw : "";
}

export function openAdLink(link?: string | null) {
  const raw = normalizeAdLink(link);
  if (!raw) return;

  if (isExternalAdLink(raw)) {
    // #ifdef H5
    window.open(raw, "_blank");
    // #endif
    // #ifndef H5
    uni.setClipboardData({
      data: raw,
      success: () => uni.showToast({ title: "链接已复制", icon: "none" }),
    });
    // #endif
    return;
  }

  if (!isInternalAdLink(raw)) return;

  const url = mapPcPathToH5(raw);
  if (!url) return;
  const pathOnly = url.split("?")[0];
  if (TAB_PAGES.has(pathOnly)) {
    uni.switchTab({ url: pathOnly });
  } else {
    uni.navigateTo({ url });
  }
}
