#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
FILES = ["D6WjvBeG.js"]

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

OLD_JOB = (
    'y(o(rt),s=>(a(),i("button",{key:"jp"+s,type:"button",class:m({on:o(K)===s}),onClick:u=>xt(s)},l(s),11,ln))'
)
NEW_JOB = (
    'y(__cpi(o(K),o(rt)),(s,ii)=>s==="\\u2026"?(a(),i("span",{key:"je"+ii,class:"pg-ellipsis"},"\\u2026")):'
    '(a(),i("button",{key:"jp"+s,type:"button",class:m({on:o(K)===s}),onClick:u=>xt(s)},l(s),11,ln))'
)

OLD_TAL = (
    'y(o(ut),s=>(a(),i("button",{key:"tp"+s,type:"button",class:m({on:o(V)===s}),onClick:u=>wt(s)},l(s),11,To))'
)
NEW_TAL = (
    'y(__cpi(o(V),o(ut)),(s,ii)=>s==="\\u2026"?(a(),i("span",{key:"te"+ii,class:"pg-ellipsis"},"\\u2026")):'
    '(a(),i("button",{key:"tp"+s,type:"button",class:m({on:o(V)===s}),onClick:u=>wt(s)},l(s),11,To))'
)

for f in FILES:
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8").read()
    if not os.path.isfile(p + ".bak_pager"):
        open(p + ".bak_pager", "w", encoding="utf-8").write(s)
    if "function __cpi(" not in s:
        # insert after imports
        last_imp = 0
        pos = 0
        while True:
            j = s.find("import", last_imp)
            if j < 0 or j > 3000:
                break
            k = s.find(";", j)
            if k < 0:
                break
            last_imp = k + 1
        s = s[:last_imp] + HELPER + s[last_imp:]
        print(f, "helper_inserted")
    n1 = s.count(OLD_JOB)
    n2 = s.count(OLD_TAL)
    print(f, "job_matches", n1, "tal_matches", n2)
    if n1:
        s = s.replace(OLD_JOB, NEW_JOB)
    if n2:
        s = s.replace(OLD_TAL, NEW_TAL)
    # also copy to cache-busted name if needed
    open(p, "w", encoding="utf-8").write(s)
    print(f, "written", len(s), "has_cpi_use", "__cpi(o(K),o(rt))" in s, "__cpi(o(V),o(ut))" in s)

# patch jobs css for ellipsis if present as hashed css
add = ".jobs-ch .pager .pg-ellipsis{min-width:24px;text-align:center;color:var(--ink3,#98a5bc);user-select:none}"
for f in os.listdir(ROOT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(ROOT, f)
    css = open(p, encoding="utf-8", errors="ignore").read()
    if ".jobs-ch .pager" in css and "pg-ellipsis" not in css:
        css = css.replace(".jobs-ch .pager{", add + ".jobs-ch .pager{", 1) if ".jobs-ch .pager{" in css else css + "\n" + add
        open(p, "w", encoding="utf-8").write(css)
        print("css", f)
