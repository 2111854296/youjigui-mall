#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
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

# Find jobs page chunk: contains both job pager buttons
cands = []
for f in os.listdir(ROOT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    js = open(p, encoding="utf-8", errors="ignore").read()
    if "招聘专区" in js or ("goJobPage" in js and "goTalPage" in js):
        cands.append((f, js, p))
        print("CAND", f, len(js))

for f, js, p in cands:
    # Look for patterns like: y(Xe,s=>(a(),r("button"...goJobPage
    # or: y(ne,p=>(... goJobPage(p)
    for m in re.finditer(r"y\(([A-Za-z0-9_$]+),([a-z0-9]+)=>", js):
        frag = js[m.start() : m.start() + 280]
        if "goJobPage" in frag or "goTalPage" in frag:
            print(f, "MATCH", frag)
    # Also button v-for: maybe compiled differently
    for needle in ["goJobPage(s)", "goJobPage(p)", "goJobPage(c)", "goTalPage(s)", "goTalPage(p)"]:
        i = js.find(needle)
        if i >= 0:
            print(f, needle, js[max(0, i - 200) : i + 120])
