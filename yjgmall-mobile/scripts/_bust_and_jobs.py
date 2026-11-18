#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"

# 1) Cache-bust news chunk: rename CfDteVJo.rg.js -> CfDteVJo.rgP.js and rewrite imports
src = os.path.join(ROOT, "CfDteVJo.rg.js")
dst_name = "CfDteVJo.rgP.js"
dst = os.path.join(ROOT, dst_name)
if os.path.isfile(src):
    shutil.copy2(src, dst)
    print("copied", dst_name)
    n = 0
    for f in os.listdir(ROOT):
        if not f.endswith(".js"):
            continue
        p = os.path.join(ROOT, f)
        s = open(p, encoding="utf-8", errors="ignore").read()
        if "CfDteVJo.rg.js" in s:
            s2 = s.replace("CfDteVJo.rg.js", dst_name)
            open(p, "w", encoding="utf-8").write(s2)
            print("rewrote import in", f)
            n += 1
    print("import_rewrites", n)

# 2) Find jobs pager loops more carefully
HELPER = (
    "function __cpi(cur,end){cur=Math.max(1,0|cur);end=Math.max(1,0|end);"
    "if(end<=7)return Array.from({length:end},(_,i)=>i+1);"
    "const set=new Set([1,end]);"
    "for(let i=cur-1;i<=cur+1;i++)if(i>=1&&i<=end)set.add(i);"
    "if(cur<=3)for(let i=2;i<=Math.min(4,end-1);i++)set.add(i);"
    "if(cur>=end-2)for(let i=Math.max(2,end-3);i<=end-1;i++)set.add(i);"
    "const sorted=[...set].sort((a,b)=>a-b),out=[];"
    "for(let i=0;i<sorted.length;i++){if(i&&sorted[i]-sorted[i-1]>1)out.push('\\u2026');out.push(sorted[i])}"
    "return out}"
)

for f in ["CSmH0f1P.js", "CSmH0f1P.cta.js", "CSmH0f1P.cta2.js", "D6WjvBeG.js", "CbXX2VGU.js"]:
    p = os.path.join(ROOT, f)
    if not os.path.isfile(p):
        continue
    s = open(p, encoding="utf-8", errors="ignore").read()
    print("====", f, "len", len(s))
    # search pager-related
    for pat in ["class:\"pager\"", "class:'pager'", ".pager", "goJobPage", "goTalPage", "last_page"]:
        print(" ", pat, s.find(pat))
    # dump around class:"pager"
    for m in re.finditer(r'class:"pager"', s):
        print("PAGER", s[m.start() - 100 : m.start() + 450])
    for m in re.finditer(r"y\(([A-Za-z0-9_$.]+),([a-z])=>", s):
        frag = s[m.start() : m.start() + 300]
        if "button" in frag and ("Page" in frag or "page" in frag or "on:" in frag):
            print("YLOOP", frag[:300])
