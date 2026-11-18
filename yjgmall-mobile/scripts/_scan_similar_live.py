#!/usr/bin/env python3
from pathlib import Path
import re
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(encoding="utf-8",errors="ignore")
# honor tab handler Ul
i=s.find("function Ul(s){")
print("Ul", s[i:i+220] if i>=0 else None)
# pe() after Be honor
print("Ul has pe", "pe()" in (s[i:i+250] if i>=0 else ""))
# toolbar locked: position only?
for pat in ["routeLocked", "e(Ie)", "e(mt)", "e(te)", "cf_", "VIP 企业"]:
    print(pat, s.count(pat))
# find filter hide for position
m=re.search(r'.{0,80}position.{0,40}', s[s.find("toolbar"):s.find("toolbar")+500] if "toolbar" in s else "")
# setHonorTab pe
print("---")
# vip filter in items?
print("vip_level filter", "vip_level" in s[s.find("Dl=w"):s.find("Dl=w")+800] if "Dl=w" in s else False)
print("Dl snip", s[s.find("Dl=w(()=>{"):s.find("Dl=w(()=>{")+350] if "Dl=w(()=>{" in s else None)
# admin layout chunk?
for p in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt").glob("*.js"):
    t=p.read_text(encoding="utf-8",errors="ignore")
    if "function menuMatch" in t or "menuMatch(" in t and "admin" in t[:200]:
        if "full.startsWith(to" in t or "startsWith(to+" in t:
            print("layout chunk", p.name)
            break
# find menuMatch in all
for p in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt").glob("*.js"):
    t=p.read_text(encoding="utf-8",errors="ignore")
    if "待审资讯" in t or "news-audit" in t:
        print("menu labels in", p.name)
    if "sub: `待审" in t or "users_pending" in t:
        print("dashboard", p.name)
