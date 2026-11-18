#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import urllib.request

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
for name in ["rMEP6MmM.js", "rMEP6MmM.ss.js"]:
    p = os.path.join(NUXT, name)
    s = open(p, encoding="utf-8").read()
    print("FILE", name)
    print("  filter", "[a(le)[a(A)]].filter(Boolean)" in s)
    print("  old_vfor", "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}" in s)
    print("  mtime", os.path.getmtime(p), "size", len(s))

# HTTP fetch localhost
for url in [
    "http://127.0.0.1/_nuxt/rMEP6MmM.js",
    "https://g.aifeili.cn/_nuxt/rMEP6MmM.js",
    "https://g.aifeili.cn/_nuxt/rMEP6MmM.ss.js",
]:
    try:
        req = urllib.request.Request(url, headers={"Cache-Control": "no-cache"})
        with urllib.request.urlopen(req, timeout=20) as r:
            body = r.read().decode("utf-8", "ignore")
        print("URL", url, "len", len(body), "filter", "[a(le)[a(A)]].filter(Boolean)" in body, "old", "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}" in body)
    except Exception as e:
        print("URL ERR", url, e)

# who still imports .js without ss?
import re
p = os.path.join(NUXT, "0a3-bBq-.js")
s = open(p, encoding="utf-8", errors="ignore").read()
print("0a3 snippets", re.findall(r"rMEP6MmM[^\"'\\s]*", s)[:10])
