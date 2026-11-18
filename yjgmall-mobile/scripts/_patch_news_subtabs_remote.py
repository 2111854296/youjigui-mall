#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

root = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
files = ["CfDteVJo.js", "CfDteVJo.cat.js", "CfDteVJo.rg.js", "CfDteVJo.sec.js"]

# 1) matchSection includes children
old_se = 'function Se(o,t){const c=String(o?.category?.slug||"");return!!(c&&c===qe[t])}'
new_se = 'function Se(o,t){const c=String(o?.category?.slug||""),p=qe[t];return!!(c&&p&&(c===p||c.startsWith(p+"-")))}'

# 2) reviewRows by tab
old_ft = 'xe=["日评","周评","月评","年度盘点"],Ft=d(()=>A("review",9))'
new_ft = (
    'xe=["日评","周评","月评","年度盘点"],'
    'Ft=d(()=>{const tab=xe[Ot.value]||xe[0],'
    'slugMap={"日评":"news-review-daily","周评":"news-review-weekly","月评":"news-review-monthly","年度盘点":"news-review-yearly"},'
    'keyMap={"日评":["日评"],"周评":["周评"],"月评":["月评"],"年度盘点":["年度盘点","年评","年度"]},'
    'slug=slugMap[tab],keys=keyMap[tab]||[tab],parent="news-review",'
    'api=(Wt.value&&Wt.value.review)||[],'
    'pool=api.length?api:Qt().filter(c=>Se(c,"review")),'
    'bound=pool.filter(c=>String(c?.category?.slug||"")===slug),'
    'ids=new Set(bound.map(c=>Number(c.id))),'
    'hit=pool.filter(c=>{if(ids.has(Number(c.id)))return!1;const s=String(c?.category?.slug||"");'
    'if(s.startsWith(parent+"-")&&s!==slug)return!1;return keys.some(k=>String(c.title||"").includes(k))});'
    'if(!bound.length&&!hit.length)return mt(pool.filter(c=>String(c?.category?.slug||"")===parent),9);'
    'return mt([...bound,...hit],9)})'
)

# 3) capacity by capTab
old_g = 'nt=w(1),g=d(()=>({capacity:A("capacity",8),trade:A("trade",8),report:A("report",8),company:A("company",8,!1),talk:A("talk",8,!1),event:A("event",8,!1),newProduct:A("product",8,!1),tech:A("tech",8,!1),expo:A("expo",8,!1)}))'
new_g = (
    'nt=w(0),g=d(()=>{const tabs=["产能","开工率"],tab=tabs[nt.value]||tabs[0],'
    'slugMap={"产能":"news-capacity-output","开工率":"news-capacity-rate"},'
    'keyMap={"产能":["产能","产量"],"开工率":["开工率","开工"]},'
    'slug=slugMap[tab],keys=keyMap[tab]||[tab],parent="news-capacity",'
    'api=(Wt.value&&Wt.value.capacity)||[],'
    'pool=api.length?api:Qt().filter(c=>Se(c,"capacity")),'
    'bound=pool.filter(c=>String(c?.category?.slug||"")===slug),'
    'ids=new Set(bound.map(c=>Number(c.id))),'
    'hit=pool.filter(c=>{if(ids.has(Number(c.id)))return!1;const s=String(c?.category?.slug||"");'
    'if(s.startsWith(parent+"-")&&s!==slug)return!1;return keys.some(k=>String(c.title||"").includes(k))});'
    'const capacity=(!bound.length&&!hit.length)'
    '?mt(pool.filter(c=>String(c?.category?.slug||"")===parent),8)'
    ':mt([...bound,...hit],8);'
    'return{capacity,trade:A("trade",8),report:A("report",8),company:A("company",8,!1),talk:A("talk",8,!1),event:A("event",8,!1),newProduct:A("product",8,!1),tech:A("tech",8,!1),expo:A("expo",8,!1)}})'
)

repls = [(old_se, new_se, "Se"), (old_ft, new_ft, "Ft"), (old_g, new_g, "g")]

for f in files:
    p = os.path.join(root, f)
    if not os.path.isfile(p):
        print(f, "MISSING")
        continue
    s = open(p, "r", encoding="utf-8").read()
    changed = []
    for old, new, tag in repls:
        n = s.count(old)
        if n == 0:
            # already?
            if tag == "Se" and 'c.startsWith(p+"-")' in s:
                changed.append(f"{tag}:already")
            elif tag == "Ft" and "news-review-daily" in s:
                changed.append(f"{tag}:already")
            elif tag == "g" and "news-capacity-output" in s:
                changed.append(f"{tag}:already")
            else:
                changed.append(f"{tag}:NO_MATCH")
            continue
        if not os.path.isfile(p + ".bak_sub"):
            open(p + ".bak_sub", "w", encoding="utf-8").write(s)
        s = s.replace(old, new)
        changed.append(f"{tag}:patched*{n}")
    open(p, "w", encoding="utf-8").write(s)
    print(f, ",".join(changed), "len", len(s))

s = open(os.path.join(root, "CfDteVJo.js"), encoding="utf-8").read()
print("verify_child_se", 'c.startsWith(p+"-")' in s)
print("verify_review", "news-review-daily" in s)
print("verify_cap", "news-capacity-output" in s)
