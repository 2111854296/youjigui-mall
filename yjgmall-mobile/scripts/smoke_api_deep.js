/**
 * 深度自测：写操作 + 详情 + 边界
 * node scripts/smoke_api_deep.js
 */
const BASE = "https://g.aifeili.cn/api/v1";

const results = { ok: [], warn: [], fail: [] };

function log(kind, status, path, extra = "") {
  const line = `${status}\t${path}${extra ? "\t" + extra : ""}`;
  console.log(kind, line);
  results[kind === "OK" ? "ok" : kind === "WARN" ? "warn" : "fail"].push({ status, path, extra });
}

async function req(path, { method = "GET", token, body } = {}) {
  const headers = { Accept: "application/json" };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body) headers["Content-Type"] = "application/json";
  let status = 0;
  let text = "";
  let json = null;
  try {
    const res = await fetch(`${BASE}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });
    status = res.status;
    text = await res.text();
    try {
      json = JSON.parse(text);
    } catch {
      /* html */
    }
  } catch (e) {
    return { status: 0, text: String(e), json: null, err: e };
  }
  return { status, text, json };
}

function msg(r) {
  if (r.json?.message) return r.json.message;
  if (r.text?.includes("页面错误")) return "HTML500";
  if (r.text?.includes("SQLSTATE")) return "SQLSTATE";
  return (r.text || "").slice(0, 100).replace(/\s+/g, " ");
}

async function login(login, password) {
  const r = await req("/auth/login", {
    method: "POST",
    body: { login, password },
  });
  if (!r.json?.token) throw new Error(`login fail ${login}: ${msg(r)}`);
  return { token: r.json.token, user: r.json.data };
}

async function expectOk(path, opts = {}, accept = [200, 201]) {
  const r = await req(path, opts);
  if (accept.includes(r.status) && r.json) {
    log("OK", r.status, `${opts.method || "GET"} ${path}`);
    return r;
  }
  if (r.status >= 500 || r.status === 0 || (r.text || "").includes("页面错误")) {
    log("FAIL", r.status, `${opts.method || "GET"} ${path}`, msg(r));
  } else {
    log("WARN", r.status, `${opts.method || "GET"} ${path}`, msg(r));
  }
  return r;
}

(async () => {
  console.log("=== AUTH ===");
  const ent = await login("2111854296", "123456");
  console.log("enterprise id=", ent.user?.id, ent.user?.username || ent.user?.name);

  // ---- detail chains from lists ----
  console.log("\n=== LIST -> DETAIL CHAINS ===");
  const chains = [
    ["/sell", (d) => `/sell/${d.id}`],
    ["/buy", (d) => `/buy/${d.id}`],
    ["/mall", (d) => `/mall/${d.id}`],
    ["/news", (d) => `/news/${d.id}`],
    ["/companies", (d) => `/companies/${d.slug || d.id}`],
    ["/quotes", (d) => `/quotes/${d.id}`],
    ["/jobs", (d) => `/jobs/${d.id}`],
    ["/know", (d) => `/know/${d.id}`],
    ["/club", (d) => `/club/${d.id}`],
    ["/gift", (d) => `/gift/${d.id}`],
    ["/brands", (d) => `/brands/${d.slug || d.id}`],
    ["/downloads", (d) => `/downloads/${d.id}`],
    ["/invest", (d) => `/invest/${d.id}`],
    ["/special", (d) => `/special/${d.id || d.slug}`],
    ["/photo", (d) => `/photo/${d.id}`],
    ["/video", (d) => `/video/${d.id}`],
    ["/group", (d) => `/group/${d.id}`],
    ["/exhibit", (d) => `/exhibit/${d.id}`],
  ];
  for (const [listPath, toDetail] of chains) {
    const list = await req(listPath);
    if (list.status >= 500) {
      log("FAIL", list.status, `GET ${listPath}`, msg(list));
      continue;
    }
    const rows = list.json?.data || [];
    if (!rows.length) {
      log("WARN", list.status, `GET ${listPath}`, "empty list (detail skipped)");
      continue;
    }
    log("OK", list.status, `GET ${listPath}`, `n=${rows.length}`);
    const dpath = toDetail(rows[0]);
    if (!dpath.includes("undefined") && !dpath.endsWith("/")) {
      await expectOk(dpath);
    }
  }

  // ---- member write smoke (create then cleanup where possible) ----
  console.log("\n=== MEMBER WRITE SMOKE ===");
  const t = ent.token;

  // alert create + delete
  {
    const c = await expectOk(
      "/member/alerts",
      { method: "POST", token: t, body: { keyword: "smoke-test-dmc", module: "sell" } },
      [200, 201],
    );
    const id = c.json?.data?.id;
    if (id) await expectOk(`/member/alerts/${id}`, { method: "DELETE", token: t }, [200]);
  }

  // ask create
  {
    await expectOk(
      "/member/asks",
      {
        method: "POST",
        token: t,
        body: { title: "冒烟测试工单", content: "自动测试，可忽略", contact_name: "test", contact_phone: "13800000000" },
      },
      [200, 201],
    );
  }

  // mail save
  {
    await expectOk(
      "/member/mail",
      { method: "PUT", token: t, body: { enabled: 1, modules: ["sell", "buy"] } },
      [200],
    );
  }

  // chat start with self should 422
  {
    const r = await req("/member/chats", {
      method: "POST",
      token: t,
      body: { peer_id: ent.user.id },
    });
    if (r.status === 422 || r.status === 400) log("OK", r.status, "POST /member/chats self", "rejected as expected");
    else if (r.status >= 500) log("FAIL", r.status, "POST /member/chats self", msg(r));
    else log("WARN", r.status, "POST /member/chats self", msg(r));
  }

  // chat start with peer (admin/seller if different)
  {
    const peer = ent.user.id === 2 ? 3 : 2;
    const c = await expectOk(
      "/member/chats",
      { method: "POST", token: t, body: { peer_id: peer } },
      [200, 201],
    );
    const sid = c.json?.data?.id;
    if (sid) {
      await expectOk(`/member/chats/${sid}/messages`, { token: t });
      await expectOk(
        `/member/chats/${sid}/messages`,
        { method: "POST", token: t, body: { content: "冒烟测试消息 " + Date.now() } },
        [200, 201],
      );
      await expectOk(`/member/chats/${sid}/messages`, { token: t });
    }
  }

  // favorite supply
  {
    const sell = await req("/sell");
    const sid = sell.json?.data?.[0]?.id;
    if (sid) {
      const f = await expectOk(
        "/member/favorites",
        { method: "POST", token: t, body: { type: "supply", id: sid } },
        [200, 201],
      );
      const fid = f.json?.data?.id;
      if (fid) await expectOk(`/member/favorites/${fid}`, { method: "DELETE", token: t }, [200]);
    }
  }

  // guestbook / inquiry (public post)
  {
    await expectOk(
      "/inquiries",
      {
        method: "POST",
        body: {
          type: "guestbook",
          title: "冒烟留言",
          content: "自动测试留言，可忽略",
          contact_name: "test",
          contact_phone: "13800000000",
        },
      },
      [200, 201],
    );
  }

  // profile-deep read + soft avatar unchanged
  {
    const p = await expectOk("/member/profile-deep", { token: t });
    if (p.json?.data || p.json) {
      await expectOk(
        "/member/profile-deep",
        {
          method: "PUT",
          token: t,
          body: {
            name: p.json?.data?.name || p.json?.name || ent.user.name,
            mobile: p.json?.data?.mobile || p.json?.mobile || "",
          },
        },
        [200],
      );
    }
  }

  // shop get
  await expectOk("/member/shop", { token: t });

  // finance / deposit / points read paths already covered; try tiny deposit validation
  {
    const r = await req("/member/deposit", { method: "POST", token: t, body: { amount: 0 } });
    if (r.status === 422 || r.status === 400) log("OK", r.status, "POST /member/deposit 0", "validated");
    else if (r.status >= 500) log("FAIL", r.status, "POST /member/deposit 0", msg(r));
    else log("WARN", r.status, "POST /member/deposit 0", msg(r));
  }

  // ---- member nested lists ----
  console.log("\n=== MEMBER NESTED ===");
  for (const p of [
    "/member/products",
    "/member/articles",
    "/member/jobs",
    "/member/know",
    "/member/club",
    "/member/reviews",
    "/member/quotes",
    "/member/inquiries",
    "/member/seller-orders",
    "/member/ad-bookings",
    "/member/spreads",
    "/member/withdraws",
    "/member/validations",
    "/member/oauth",
    "/member/friends",
  ]) {
    await expectOk(p, { token: t });
  }

  // first item show for products/articles if any
  {
    const pr = await req("/member/products", { token: t });
    const id = pr.json?.data?.[0]?.id;
    if (id) await expectOk(`/member/products/${id}`, { token: t });
    const ar = await req("/member/articles", { token: t });
    const aid = ar.json?.data?.[0]?.id;
    if (aid) await expectOk(`/member/articles/${aid}`, { token: t });
  }

  console.log("\n======== DEEP SUMMARY ========");
  console.log(`OK=${results.ok.length} WARN=${results.warn.length} FAIL=${results.fail.length}`);
  if (results.fail.length) {
    console.log("\nFAILED:");
    for (const f of results.fail) console.log(`- ${f.status} ${f.path} :: ${f.extra}`);
    process.exit(1);
  }
  if (results.warn.length) {
    console.log("\nWARNINGS:");
    for (const w of results.warn) console.log(`- ${w.status} ${w.path} :: ${w.extra}`);
  }
  process.exit(0);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
