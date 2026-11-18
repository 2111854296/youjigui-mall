#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
p = os.path.join(NUXT, "0a3-bBq-.js")
s = open(p, encoding="utf-8", errors="ignore").read()
for m in re.finditer(r".{0,40}rMEP6MmM.{0,40}", s):
    print(repr(m.group(0)))

# force replace any variant
nt = re.sub(r"rMEP6MmM(?:\.ss2|\.ss|\.pg)?\.js", "rMEP6MmM.ss2.js", s)
# also bare patterns without .js?
nt2 = re.sub(r"rMEP6MmM(?:\.ss2|\.ss)?(?=\.js)", "rMEP6MmM.ss2", s)
# simpler: find exact
ids = set(re.findall(r"rMEP6MmM[\w.]*", s))
print("ids", ids)
for old in sorted(ids, key=len, reverse=True):
    if old.endswith(".js"):
        s = s.replace(old, "rMEP6MmM.ss2.js")
    else:
        # leave
        pass
# if ids are like rMEP6MmM.js
s2 = open(p, encoding="utf-8", errors="ignore").read()
s2 = s2.replace("rMEP6MmM.ss.js", "rMEP6MmM.ss2.js").replace("rMEP6MmM.js", "rMEP6MmM.ss2.js")
# avoid double
s2 = s2.replace("rMEP6MmM.ss2.ss2.js", "rMEP6MmM.ss2.js")
open(p, "w", encoding="utf-8").write(s2)
print("after")
for m in re.finditer(r".{0,40}rMEP6MmM.{0,40}", s2):
    print(repr(m.group(0)))
print("file exists", os.path.isfile(os.path.join(NUXT, "rMEP6MmM.ss2.js")))
