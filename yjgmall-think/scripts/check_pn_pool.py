#!/usr/bin/env python3
import json
import urllib.request

d = json.load(urllib.request.urlopen("https://g.aifeili.cn/api/v1/news?per_page=40&sort=new", timeout=20))
rows = d.get("data") or []
print("list_n", len(rows))
print(
    "quote_in_list",
    [
        (r.get("id"), r.get("type"), r.get("quote_product_id"), (r.get("title") or "")[:28])
        for r in rows
        if r.get("type") == "quote" or r.get("quote_product_id")
    ],
)
d2 = json.load(urllib.request.urlopen("https://g.aifeili.cn/api/v1/news?type=quote&per_page=20", timeout=20))
rows2 = d2.get("data") or []
print("type_quote_n", len(rows2))
for r in rows2:
    print(r.get("id"), r.get("quote_product_id"), (r.get("quote_product") or {}).get("name"), (r.get("title") or "")[:36])

s = open("/www/wwwroot/g.aifeili.cn/public/_nuxt/CfDteVJo.js", encoding="utf-8").read()
i = s.find("ft=d(()=>{const o=ht.value")
print("FT", s[i : i + 700])
i = s.find("function Se(o,t)")
print("SE", s[i : i + 180])
