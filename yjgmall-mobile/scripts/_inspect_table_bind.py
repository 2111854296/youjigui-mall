#!/usr/bin/env python3
from pathlib import Path
import re

s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(
    encoding="utf-8", errors="ignore"
)
# el-table data binding
for m in re.finditer(r".{0,40}data:.{0,40}Dl.{0,40}", s):
    print("DATA", m.group(0)[:120])
for m in re.finditer(r".{0,30}:data.{0,50}", s):
    if "Dl" in m.group(0) or "Dt" in m.group(0) or "items" in m.group(0):
        print("BIND", m.group(0)[:140])
# how many times Dt.value?.data is used raw in template-ish
print("Dt.data count", s.count("Dt.value?.data"))
print("Dl count", s.count("Dl"))
# ae() body start
m = re.search(r"function ae\(\)\{.{0,350}", s)
print("AE", m.group(0) if m else None)
# Je search
m = re.search(r"async function Je\(\)\{.{0,120}", s)
print("JE", m.group(0) if m else None)
# who calls ae(
print("ae calls", len(re.findall(r"\bae\(\)", s)))
