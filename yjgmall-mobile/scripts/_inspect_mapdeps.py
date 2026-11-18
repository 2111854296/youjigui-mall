#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re
p = "/www/wwwroot/g.aifeili.cn/public/_nuxt/0a3-bBq-.js"
s = open(p, encoding="utf-8", errors="ignore").read()
# find __vite__mapDeps
i = s.find("__vite__mapDeps")
print("mapDeps def", s[i : i + 500])
# find all rMEP
for m in re.finditer(r"rMEP6MmM[\w.]*", s):
    print("ref", m.group(0), "at", m.start())
# find preload helpers
for m in re.finditer(r".{0,30}rMEP6MmM.{0,80}", s):
    print("ctx", m.group(0)[:120])
