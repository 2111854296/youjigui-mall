#!/usr/bin/env python3
from pathlib import Path
import re

nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for f in nuxt.glob("*.js"):
    try:
        s = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "admin/resources/" not in s:
        continue
    hits = sum(
        1
        for p in ("routeLockedStatus", "effectiveStatus", "admin-res-", "syncQueryFilters")
        if p in s
    )
    if hits == 0 and "status:e." not in s and "status:" not in s:
        continue
    if "admin-res-" not in s and "routeLockedStatus" not in s and "effectiveStatus" not in s:
        continue
    print("FILE", f.name, "size", len(s))
    for pat in [
        "routeLockedStatus",
        "effectiveStatus",
        "admin-res-",
        "syncQueryFilters",
        "query.status",
        "status.value",
    ]:
        print(" ", pat, s.count(pat))
    for m in re.finditer(r".{0,50}admin-res-.{0,140}", s):
        print(" KEY", m.group(0)[:200])
        break
    for m in re.finditer(r".{0,30}status:.{0,60}vip:.{0,40}", s):
        print(" Q", m.group(0)[:180])
        break
    for m in re.finditer(r".{0,40}query\.status.{0,100}", s):
        print(" RQ", m.group(0)[:180])
        break
