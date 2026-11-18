#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in sorted(nuxt.glob("*.js"), key=lambda x: -x.stat().st_size)[:80]:
    t=p.read_text(encoding="utf-8",errors="ignore")
    if "待审资讯" in t or "/admin/r/articles?status=pending" in t:
        print("has pending label", p.name)
    if 'includes("?")' in t and "admin" in t and ("fullPath" in t or "full.path" in t or "routeFull" in t):
        print("maybe match", p.name)
    if "badge.parent" in t or "badge child" in t:
        print("badge css", p.name)
    if "openeds" in t and "admin-menu" in t:
        print("layout-ish", p.name, p.stat().st_size)
