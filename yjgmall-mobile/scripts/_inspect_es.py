#!/usr/bin/env python3
from pathlib import Path
import re

s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(
    encoding="utf-8", errors="ignore"
)
# find _es definition and surrounding list query
for m in re.finditer(r".{0,200}status:_es\|\|void 0.{0,200}", s):
    print("LISTQ", m.group(0))
    print("---")
for m in re.finditer(r".{0,120}_es[=:].{0,200}", s):
    t = m.group(0)
    if "status" in t or "query" in t or "pending" in t:
        print("ES", t[:280])
        print("---")
# how c (status ref) relates to query
for m in re.finditer(r"function ae\(\).{0,500}", s):
    print("AE", m.group(0)[:500])
    print("---")
# watch route
for m in re.finditer(r"fullPath.{0,250}", s):
    print("FP", m.group(0)[:280])
    print("---")
    break
# el-select status change?
for m in re.finditer(r".{0,80}全部状态.{0,200}", s):
    print("SEL", m.group(0)[:280])
    print("---")
