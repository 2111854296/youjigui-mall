#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

root = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
old = (
    'ft=d(()=>{const o=ht.value,t=Ce[o]||[o],l=Qt().filter(c=>t.some(p=>String(c.title||"").includes(p)));return mt(l,8)})'
)
new = (
    'ft=d(()=>{const o=ht.value,t=Ce[o]||[o],N=Qt(),'
    'bound=N.filter(c=>{const pid=Number(c.quote_product_id||c.quote_product&&c.quote_product.id||0);'
    'if(!pid)return!1;const name=String(c.quote_product&&c.quote_product.name||"");'
    'return t.some(p=>name.includes(p))||name.includes(o)}),'
    'ids=new Set(bound.map(c=>Number(c.id))),'
    'hit=N.filter(c=>{if(ids.has(Number(c.id)))return!1;'
    'if(Number(c.quote_product_id||c.quote_product&&c.quote_product.id||0))return!1;'
    'return t.some(p=>String(c.title||"").includes(p))});'
    'return mt([...bound,...hit],8)})'
)
files = ["CfDteVJo.js", "CfDteVJo.cat.js", "CfDteVJo.rg.js", "CfDteVJo.sec.js"]
for f in files:
    p = os.path.join(root, f)
    if not os.path.isfile(p):
        print(f, "MISSING")
        continue
    s = open(p, "r", encoding="utf-8").read()
    n = s.count(old)
    if n == 0:
        print(f, "already" if "quote_product_id||c.quote_product" in s else "NO_MATCH")
        continue
    open(p + ".bak_qp", "w", encoding="utf-8").write(s)
    s2 = s.replace(old, new)
    open(p, "w", encoding="utf-8").write(s2)
    print(f, "patched", n, "newlen", len(s2))

s = open(os.path.join(root, "CfDteVJo.js"), encoding="utf-8").read()
print("verify_bind", "quote_product_id||c.quote_product" in s)
print("verify_old_gone", old not in s)
i = s.find("ft=d(()=>{const o=ht.value")
print(s[i : i + 480])
