#!/usr/bin/env python3
from pathlib import Path
import re

s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(
    encoding="utf-8", errors="ignore"
)
i = s.find("admin-res-")
print(s[i : i + 900])
print("---INGEST---")
for m in re.finditer(r"7691/ingest[^`]{0,400}", s):
    print(m.group(0)[:400])
    print("---")
