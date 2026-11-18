#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
# ensure 0a3 references ss.js
p = os.path.join(NUXT, "0a3-bBq-.js")
s = open(p, encoding="utf-8", errors="ignore").read()
print("0a3 has rMEP6MmM.js", "rMEP6MmM.js" in s)
print("0a3 has rMEP6MmM.ss.js", "rMEP6MmM.ss.js" in s)
if "rMEP6MmM.js" in s and "rMEP6MmM.ss.js" not in s:
    open(p, "w", encoding="utf-8").write(s.replace("rMEP6MmM.js", "rMEP6MmM.ss.js"))
    print("fixed 0a3 bust")
# check ss content
ss = open(os.path.join(NUXT, "rMEP6MmM.ss.js"), encoding="utf-8").read()
print("ss has filter", "[a(le)[a(A)]].filter(Boolean)" in ss)
print("ss still full vfor slides", ss.count("q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}"))
print("ss dots intact", 'o("button",{key:"d"+m' in ss and "q(a(le),(n,m)=>(r(),o(\"button\"" in ss or 'q(a(le),(n,m)=>(r(),o("button"' in ss)
