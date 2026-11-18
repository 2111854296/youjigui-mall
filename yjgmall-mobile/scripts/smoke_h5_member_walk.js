/**
 * H5 会员菜单逐页探测（需本机已登录态 token，或脚本内登录注入）
 * 用法：在已打开的浏览器里不便跑时，本脚本用 fetch 探测每页依赖的 API；
 * 浏览器逐页由 agent 配合 CDP 完成。
 *
 * node scripts/smoke_h5_member_walk.js
 */
const BASE = "https://g.aifeili.cn/api/v1";
const H5 = "https://g.aifeili.cn/h5/index.html#";

/** 与 member/index.vue 菜单顺序一致 */
const PAGES = [
  { name: "站内信件", path: "/pages/member/messages", apis: ["/member/messages"] },
  { name: "站内交谈", path: "/pages/member/chat", apis: ["/member/chats"] },
  { name: "我的商友", path: "/pages/member/friends", apis: ["/member/friends"] },
  { name: "商机收藏", path: "/pages/member/favorites", apis: ["/member/favorites"] },
  { name: "贸易提醒", path: "/pages/member/alerts", apis: ["/member/alerts"] },
  { name: "邮件订阅", path: "/pages/member/mail", apis: ["/member/mail"] },
  { name: "排名推广", path: "/pages/member/spread", apis: ["/member/spreads"] },
  { name: "广告预定", path: "/pages/ads/index", apis: ["/ads"] },
  { name: "一键登录", path: "/pages/member/oauth", apis: ["/member/oauth"] },
  { name: "修改资料", path: "/pages/member/profile", apis: ["/auth/me"] },
  { name: "头像设置", path: "/pages/member/avatar", apis: ["/auth/me"] },
  { name: "密码管理", path: "/pages/member/password", apis: [] },
  { name: "会员认证", path: "/pages/member/validate", apis: ["/member/validations"] },
  { name: "会员升级", path: "/pages/member/grade", apis: ["/vip/grades"] },
  { name: "客服中心", path: "/pages/member/ask", apis: ["/member/asks"] },
  { name: "供应管理", path: "/pages/member/supplies", apis: ["/member/supplies"] },
  { name: "采购管理", path: "/pages/member/purchases", apis: ["/member/purchases"] },
  { name: "商品管理", path: "/pages/member/products", apis: ["/member/products"] },
  { name: "招聘管理", path: "/pages/member/jobs", apis: ["/member/jobs"] },
  { name: "知道管理", path: "/pages/member/know", apis: ["/member/know"] },
  { name: "商圈管理", path: "/pages/member/club", apis: ["/member/club"] },
  { name: "询盘管理", path: "/pages/member/inquiries", apis: ["/member/inquiries"] },
  { name: "收到报价", path: "/pages/member/quotes", apis: ["/member/quotes"] },
  { name: "评价管理", path: "/pages/member/reviews", apis: ["/member/reviews"] },
  { name: "我的订单", path: "/pages/member/orders", apis: ["/member/orders"] },
  { name: "售出订单", path: "/pages/member/seller-orders", apis: ["/member/seller-orders"] },
  { name: "购物车", path: "/pages/member/cart", apis: ["/member/cart"] },
  { name: "收货地址", path: "/pages/member/addresses", apis: ["/member/addresses"] },
  { name: "资金流水", path: "/pages/member/finance", apis: ["/member/finance"] },
  { name: "保证金", path: "/pages/member/deposit", apis: ["/member/finance"] },
  { name: "申请提现", path: "/pages/member/cash", apis: ["/member/withdraws"] },
  { name: "购买积分", path: "/pages/member/credit", apis: ["/member/finance"] },
  { name: "旺铺设置", path: "/pages/member/shop", apis: ["/member/shop"] },
  { name: "公司新闻", path: "/pages/member/shop-news", apis: ["/member/shop"] },
  { name: "荣誉资质", path: "/pages/member/shop-honors", apis: ["/member/shop"] },
  { name: "公司单页", path: "/pages/member/shop-pages", apis: ["/member/shop"] },
  { name: "友情链接", path: "/pages/member/shop-links", apis: ["/member/shop"] },
];

async function req(path, token) {
  const res = await fetch(`${BASE}${path}`, {
    headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
  });
  const text = await res.text();
  let json = null;
  try {
    json = JSON.parse(text);
  } catch {
    /* */
  }
  return { status: res.status, text, json };
}

function brief(r) {
  if (r.json?.message) return String(r.json.message).slice(0, 80);
  if ((r.text || "").includes("页面错误")) return "HTML500";
  if ((r.text || "").includes("SQLSTATE")) return "SQLSTATE";
  if (r.json?.data !== undefined) {
    const d = r.json.data;
    if (Array.isArray(d)) return `list=${d.length}`;
    if (d && typeof d === "object" && Array.isArray(d.data)) return `list=${d.data.length}`;
    if (d && typeof d === "object" && Array.isArray(d.list)) return `list=${d.list.length}`;
    return "obj";
  }
  return (r.text || "").slice(0, 60).replace(/\s+/g, " ");
}

(async () => {
  const login = await req("/auth/login", "");
  // login needs POST
  const lr = await fetch(`${BASE}/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ login: "2111854296", password: "123456" }),
  });
  const lj = await lr.json();
  if (!lj.token) throw new Error("login failed " + JSON.stringify(lj).slice(0, 200));
  const token = lj.token;
  console.log("login ok user=", lj.data?.id, lj.data?.username || lj.data?.name);
  console.log("H5 base", H5);
  console.log("");

  let ok = 0,
    warn = 0,
    fail = 0;
  for (const p of PAGES) {
    if (!p.apis.length) {
      console.log("SKIP\t" + p.name + "\t" + p.path + "\t(no api)");
      continue;
    }
    for (const api of p.apis) {
      const r = await req(api, token);
      const b = brief(r);
      if (r.status >= 500 || (r.text || "").includes("页面错误") || r.status === 0) {
        console.log("FAIL\t" + p.name + "\t" + api + "\t" + r.status + "\t" + b);
        fail++;
      } else if (r.status >= 400) {
        console.log("WARN\t" + p.name + "\t" + api + "\t" + r.status + "\t" + b);
        warn++;
      } else {
        console.log("OK\t" + p.name + "\t" + api + "\t" + r.status + "\t" + b);
        ok++;
      }
    }
  }
  console.log("\n=== SUMMARY api ===");
  console.log("ok=%d warn=%d fail=%d", ok, warn, fail);
  if (fail) process.exit(1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
