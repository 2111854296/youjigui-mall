#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
FILES = [
    "CfDteVJo.rgP.js",
    "CfDteVJo.js",
    "CfDteVJo.rg.js",
    "CfDteVJo.cat.js",
    "CfDteVJo.sec.js",
]

OLD_Q = "q:sec?void 0:j.value||void 0"
NEW_Q = "q:j.value||void 0"

# Header: h1 + more-link → h1 + tools(search form + more-link)
OLD_HD = (
    'e("div",as,[e("h1",null,i(n(we)),1),q(l,{class:"more-link",to:"/news"},'
    '{default:v(()=>[...t[8]||(t[8]=[m("返回资讯首页 ›",-1)])]),_:1})])'
)

# Note: q() here is createBlock/createVNode alias in the bundle — keep that name for NuxtLink.
# Form uses native GET so no router wiring needed.
NEW_HD = (
    'e("div",as,[e("h1",null,i(n(we)),1),'
    'e("div",{class:"lp-tools"},['
    'e("form",{class:"lp-search",method:"get",action:"/news",onSubmit:E(ev=>{'
    'const fd=new FormData(ev.target);'
    'const kw=String(fd.get("q")||"").trim();'
    'const u=new URL("/news",location.origin);'
    'const sec=String((n(R).query&&n(R).query.section)||"");'
    'const cat=String((n(R).query&&n(R).query.category)||"");'
    'if(sec)u.searchParams.set("section",sec);'
    'if(cat)u.searchParams.set("category",cat);'
    'if(kw)u.searchParams.set("q",kw);'
    'location.href=u.pathname+u.search;'
    '},["prevent"])},['
    'e("input",{type:"search",name:"q",value:n(j)||"",placeholder:"搜索资讯标题关键词","aria-label":"关键词搜索"}),'
    'e("button",{type:"submit"},"搜索")'
    ']),'
    'q(l,{class:"more-link",to:"/news"},{default:v(()=>[...t[8]||(t[8]=[m("返回资讯首页 ›",-1)])]),_:1})'
    '])])'
)

CSS_ADD = (
    ".news-portal .list-panel .lp-tools{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-left:auto}"
    ".news-portal .list-panel .lp-search{display:flex;align-items:center;gap:6px}"
    ".news-portal .list-panel .lp-search input{width:min(260px,52vw);height:34px;padding:0 12px;border:1px solid var(--line,#e9eef6);border-radius:8px;font-size:13.5px;color:var(--ink);background:#fff;outline:none}"
    ".news-portal .list-panel .lp-search input:focus{border-color:var(--blue,#2468f2);box-shadow:0 0 0 3px rgba(36,104,242,.12)}"
    ".news-portal .list-panel .lp-search button{height:34px;padding:0 12px;border:1px solid var(--blue,#2468f2);border-radius:8px;background:var(--blue,#2468f2);color:#fff;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit}"
)

for f in FILES:
    p = os.path.join(ROOT, f)
    if not os.path.isfile(p):
        print(f, "MISSING")
        continue
    s = open(p, encoding="utf-8").read()
    if not os.path.isfile(p + ".bak_search"):
        open(p + ".bak_search", "w", encoding="utf-8").write(s)
    nq = s.count(OLD_Q)
    nh = s.count(OLD_HD)
    print(f, "q_matches", nq, "hd_matches", nh)
    if nq:
        s = s.replace(OLD_Q, NEW_Q)
    if nh:
        s = s.replace(OLD_HD, NEW_HD)
    elif "lp-search" in s:
        print(f, "hd already")
    else:
        print(f, "HD_NO_MATCH")
        i = s.find("返回资讯首页")
        print(s[max(0, i - 200) : i + 100])
    open(p, "w", encoding="utf-8").write(s)
    print(f, "done", "q_ok", NEW_Q in s or "q:j.value||void 0" in s, "search", "lp-search" in s)

# CSS into hashed news css + entry
for f in os.listdir(ROOT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(ROOT, f)
    css = open(p, encoding="utf-8", errors="ignore").read()
    if ".news-portal .list-panel .lp-hd" in css and "lp-search" not in css:
        css = css + "\n" + CSS_ADD + "\n"
        open(p, "w", encoding="utf-8").write(css)
        print("css", f)

# Also append to entry css
for f in os.listdir(ROOT):
    if f.startswith("entry.") and f.endswith(".css"):
        p = os.path.join(ROOT, f)
        css = open(p, encoding="utf-8", errors="ignore").read()
        if "lp-search" not in css:
            open(p, "a", encoding="utf-8").write("\n" + CSS_ADD + "\n")
            print("entry_css", f)

# cache bust news chunk again
src = os.path.join(ROOT, "CfDteVJo.rgP.js")
dst = os.path.join(ROOT, "CfDteVJo.rgS.js")
if os.path.isfile(src):
    shutil.copy2(src, dst)
    n = 0
    for f in os.listdir(ROOT):
        if not f.endswith(".js"):
            continue
        p = os.path.join(ROOT, f)
        s = open(p, encoding="utf-8", errors="ignore").read()
        if "CfDteVJo.rgP.js" in s:
            open(p, "w", encoding="utf-8").write(s.replace("CfDteVJo.rgP.js", "CfDteVJo.rgS.js"))
            print("bust", f)
            n += 1
    print("bust_n", n)
