#!/usr/bin/env python3
from pathlib import Path
import re

f = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s = f.read_text(encoding="utf-8", errors="ignore")
for m in re.finditer(r".{0,80}quote-products.{0,100}", s):
    t = m.group(0)
    if any(x in t for x in ("340", "220", "320", "360", "min", "操作", "ops")):
        print("CTX:", t.replace("\n", " ")[:240])
for m in re.finditer(r".{0,80}label:\"操作\".{0,160}", s):
    print("OPS:", m.group(0).replace("\n", " ")[:280])
for pat in [
    'resource==="quote-products"?340:220',
    "resource===\"quote-products\"?340:220",
    'resource==="quote-products"?340',
    '?340:220',
    '?360:320',
]:
    print("HAS", pat, pat in s)
