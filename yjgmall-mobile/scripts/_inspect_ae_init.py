#!/usr/bin/env python3
from pathlib import Path
import re

s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(
    encoding="utf-8", errors="ignore"
)
# find ae() invocations
print("ae() calls", len(re.findall(r"\bae\(\)", s)))
for m in re.finditer(r".{0,30}\bae\(\).{0,30}", s):
    print("CALL", m.group(0))
# order: first ae vs useAsyncData / Wt(
idx_ae_fn = s.find("function ae()")
idx_ol = s.find("function Ol()")
idx_async = s.find("admin-res-")
print("pos ae_fn", idx_ae_fn, "ol", idx_ol, "asyncKey", idx_async)
# snippet around admin-res setup including preceding 500 chars
i = s.find("admin-res-")
print("BEFORE_ASYNC", s[max(0, i - 400) : i + 80])
print("---")
# after ae function, is ae() called once?
chunk = s[idx_ae_fn : idx_ae_fn + 2500]
print("AFTER_AE_FN_TAIL", chunk[-400:])
