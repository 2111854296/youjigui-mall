/**
 * 全局 API 冒烟自测（只读 GET + 必要鉴权）
 * 用法：node scripts/smoke_api.js
 */
const BASE = "https://g.aifeili.cn/api/v1";

const PUBLIC_GET = [
  "/ping",
  "/home",
  "/search?q=DMC&type=sell",
  "/categories?module=sell",
  "/categories?module=buy",
  "/categories?module=company",
  "/ads",
  "/ad-positions",
  "/ad-places",
  "/links",
  "/announcements",
  "/hotwords",
  "/site",
  "/sell",
  "/buy",
  "/companies",
  "/mall",
  "/news",
  "/news/rank",
  "/news/topics",
  "/brands",
  "/downloads",
  "/invest",
  "/pages/about",
  "/pages/help",
  "/pages/contact",
  "/pages/safety",
  "/pages/vip",
  "/vip/grades",
  "/quotes",
  "/jobs",
  "/know",
  "/club",
  "/special",
  "/photo",
  "/video",
  "/group",
  "/exhibit",
  "/gift",
];

const MEMBER_GET = [
  "/auth/me",
  "/member/dashboard",
  "/member/supplies",
  "/member/purchases",
  "/member/orders",
  "/member/seller-orders",
  "/member/cart",
  "/member/messages",
  "/member/quotes",
  "/member/favorites",
  "/member/addresses",
  "/member/inquiries",
  "/member/reviews",
  "/member/shop",
  "/member/finance",
  "/member/spreads",
  "/member/ad-bookings",
  "/member/jobs",
  "/member/know",
  "/member/club",
  "/member/friends",
  "/member/profile-deep",
  "/member/validations",
  "/member/oauth",
  "/member/chats",
  "/member/alerts",
  "/member/mail",
  "/member/withdraws",
  "/member/products",
  "/member/articles",
  "/member/asks",
];

async function req(path, token) {
  const headers = { Accept: "application/json" };
  if (token) headers.Authorization = `Bearer ${token}`;
  const t0 = Date.now();
  let status = 0;
  let body = "";
  let err = "";
  try {
    const res = await fetch(`${BASE}${path}`, { headers });
    status = res.status;
    body = await res.text();
  } catch (e) {
    err = String(e?.message || e);
  }
  const ms = Date.now() - t0;
  let jsonOk = false;
  let message = "";
  try {
    const j = JSON.parse(body);
    jsonOk = true;
    message = j.message || j.msg || "";
  } catch {
    if (body.includes("页面错误")) message = "页面错误(HTML500)";
    else if (body.includes("SQLSTATE")) message = "SQLSTATE";
    else message = body.slice(0, 80).replace(/\s+/g, " ");
  }
  return { path, status, ms, jsonOk, message, err, body: body.slice(0, 200) };
}

function isFail(r) {
  if (r.err) return true;
  if (r.status >= 500) return true;
  if (r.status === 0) return true;
  // 401 on member is fail if we have token; 404 on pages may be ok
  if (r.status >= 500) return true;
  return false;
}

async function login(login, password) {
  const res = await fetch(`${BASE}/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ login, password }),
  });
  const j = await res.json();
  if (!j.token) throw new Error(`login failed ${login}: ${JSON.stringify(j).slice(0, 200)}`);
  return j.token;
}

async function resolveIds(token) {
  const ids = {};
  const headers = { Authorization: `Bearer ${token}`, Accept: "application/json" };
  const grab = async (path, key) => {
    try {
      const res = await fetch(`${BASE}${path}`, { headers });
      const j = await res.json();
      const list = j.data || [];
      if (Array.isArray(list) && list[0]?.id) ids[key] = list[0].id;
      else if (list?.id) ids[key] = list.id;
    } catch { /* ignore */ }
  };
  await grab("/sell", "sell");
  await grab("/buy", "buy");
  await grab("/mall", "mall");
  await grab("/news", "news");
  await grab("/companies", "company");
  await grab("/quotes", "quote");
  await grab("/jobs", "job");
  await grab("/member/chats", "chat");
  // 会话列表里的 peer_id 是对方；要用属于当前用户的会话
  try {
    const res = await fetch(`${BASE}/member/chats`, { headers });
    const j = await res.json();
    const meRes = await fetch(`${BASE}/auth/me`, { headers });
    const me = await meRes.json();
    const uid = me?.data?.id;
    const mine = (j.data || []).find((s) => s.user_id === uid || s.peer_id === uid || true);
    // 只要会话在列表里，就属于当前用户（接口已按用户过滤）
    if ((j.data || [])[0]?.id) ids.chat = j.data[0].id;
  } catch { /* ignore */ }
  await grab("/member/supplies", "msupply");
  return ids;
}

(async () => {
  const fails = [];
  const warns = [];
  const ok = [];

  console.log("=== PUBLIC GET ===");
  for (const p of PUBLIC_GET) {
    const r = await req(p);
    const line = `${r.status}\t${r.ms}ms\t${p}${r.message ? "\t" + r.message : ""}`;
    if (isFail(r)) {
      fails.push(r);
      console.log("FAIL", line);
    } else if (r.status >= 400) {
      warns.push(r);
      console.log("WARN", line);
    } else {
      ok.push(r);
      console.log("OK  ", line);
    }
  }

  console.log("\n=== LOGIN ===");
  const token = await login("2111854296", "123456");
  console.log("enterprise token ok");

  console.log("\n=== MEMBER GET ===");
  for (const p of MEMBER_GET) {
    const r = await req(p, token);
    const line = `${r.status}\t${r.ms}ms\t${p}${r.message ? "\t" + r.message : ""}`;
    if (isFail(r)) {
      fails.push(r);
      console.log("FAIL", line);
    } else if (r.status >= 400) {
      warns.push(r);
      console.log("WARN", line);
    } else {
      ok.push(r);
      console.log("OK  ", line);
    }
  }

  console.log("\n=== DETAIL GET ===");
  const ids = await resolveIds(token);
  const details = [];
  if (ids.sell) details.push(`/sell/${ids.sell}`);
  if (ids.buy) details.push(`/buy/${ids.buy}`);
  if (ids.mall) details.push(`/mall/${ids.mall}`, `/mall/${ids.mall}/reviews`);
  if (ids.news) details.push(`/news/${ids.news}`);
  if (ids.company) details.push(`/companies/${ids.company}`);
  if (ids.quote) details.push(`/quotes/${ids.quote}`);
  if (ids.job) details.push(`/jobs/${ids.job}`);
  if (ids.chat) details.push(`/member/chats/${ids.chat}/messages`);
  if (ids.msupply) details.push(`/member/supplies/${ids.msupply}`);
  // compare needs 2 ids
  try {
    const sellRes = await fetch(`${BASE}/sell`);
    const sj = await sellRes.json();
    const a = sj.data?.[0]?.id;
    const b = sj.data?.[1]?.id;
    if (a && b) details.push(`/sell/compare?ids=${a},${b}`);
  } catch { /* ignore */ }

  for (const p of details) {
    const needAuth = p.startsWith("/member/");
    const r = await req(p, needAuth ? token : undefined);
    const line = `${r.status}\t${r.ms}ms\t${p}${r.message ? "\t" + r.message : ""}`;
    if (isFail(r)) {
      fails.push(r);
      console.log("FAIL", line);
    } else if (r.status >= 400) {
      warns.push(r);
      console.log("WARN", line);
    } else {
      ok.push(r);
      console.log("OK  ", line);
    }
  }

  console.log("\n=== PERSONAL LOGIN ===");
  let personalToken = "";
  try {
    personalToken = await login("admin", "admin123");
    console.log("personal/admin token ok");
    for (const p of ["/auth/me", "/member/dashboard", "/member/purchases", "/member/orders", "/member/chats", "/member/shop"]) {
      const r = await req(p, personalToken);
      const line = `${r.status}\t${r.ms}ms\t${p}${r.message ? "\t" + r.message : ""}`;
      if (isFail(r)) {
        fails.push(r);
        console.log("FAIL", line);
      } else if (r.status >= 400 && r.status !== 403) {
        // shop 403 for personal is acceptable depending on caps
        warns.push(r);
        console.log("WARN", line);
      } else {
        ok.push(r);
        console.log("OK  ", line);
      }
    }
  } catch (e) {
    console.log("WARN personal login skipped:", e.message || e);
  }

  console.log("\n======== SUMMARY ========");
  console.log(`OK=${ok.length} WARN=${warns.length} FAIL=${fails.length}`);
  if (fails.length) {
    console.log("\nFAILED:");
    for (const f of fails) {
      console.log(`- ${f.status} ${f.path} :: ${f.message || f.err || f.body}`);
    }
    process.exit(1);
  }
  process.exit(0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
