#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"

# Injectable compact page helper (minified)
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

# News CfDteVJo naive pager (exact from live)
OLD_NEWS = (
    'n(qt)?.last_page>1?(a(),r("div",ps,[(a(!0),r(_,null,y(n(qt).last_page,s=>(a(),k(l,{key:s,class:f({on:s===n(W)}),to:{path:"/news",query:{...n(R).query,page:s===1?void 0:s}}},{default:v(()=>[m(i(s),1)]),_:2},1032,["class","to"]))),128))])):b("",!0)'
)

# Compact: map over __cpi; ellipsis as span, else NuxtLink
NEW_NEWS = (
    'n(qt)?.last_page>1?(a(),r("div",ps,[(a(!0),r(_,null,y(__cpi(n(W),n(qt).last_page),(s,ii)=>'
    's==="\\u2026"?(a(),r("span",{key:"e"+ii,class:"pg-ellipsis"},"\\u2026")):'
    '(a(),k(l,{key:s,class:f({on:s===n(W)}),to:{path:"/news",query:{...n(R).query,page:s===1?void 0:s}}},'
    '{default:v(()=>[m(i(s),1)]),_:2},1032,["class","to"]))),128))])):b("",!0)'
)

NEWS_FILES = ["CfDteVJo.js", "CfDteVJo.cat.js", "CfDteVJo.rg.js", "CfDteVJo.sec.js"]

# Also inject CSS for ellipsis via a tiny style if missing — news-portal uses .pager already.
# Skip CSS inject; numbers alone work; ellipsis as text is fine.


def patch_file(path, old, new, tag):
    if not os.path.isfile(path):
        print(tag, "MISSING", path)
        return False
    s = open(path, "r", encoding="utf-8").read()
    if "__cpi(" in s and "pg-ellipsis" in s and old not in s:
        print(tag, "already")
        return True
    if old not in s:
        print(tag, "NO_MATCH")
        # show nearby
        i = s.find("last_page>1")
        print(" near", s[i : i + 220] if i >= 0 else "none")
        return False
    if not os.path.isfile(path + ".bak_pager"):
        open(path + ".bak_pager", "w", encoding="utf-8").write(s)
    if "function __cpi(" not in s:
        # insert helper after first import block / at start after imports
        # safest: prepend after last import line
        idx = 0
        while True:
            if s.startswith("import", idx) or (idx > 0 and s[idx : idx + 6] == "import"):
                pass
            # find end of import section
            break
        # Put helper right before first "const " or "function " after imports
        m = 0
        pos = 0
        while s.startswith("import", pos) or (pos < len(s) and s[pos : pos + 7] == "import{"):
            nl = s.find(";", pos)
            if nl < 0:
                break
            pos = nl + 1
            while pos < len(s) and s[pos] in "\r\n":
                pos += 1
            m = pos
        # also handle import ... from "..."
        pos = 0
        last_imp = 0
        while True:
            j = s.find("import", last_imp if last_imp == 0 else last_imp)
            if j < 0 or j > 2000:
                break
            # end of statement
            k = s.find(";", j)
            if k < 0:
                break
            last_imp = k + 1
        insert_at = last_imp
        s = s[:insert_at] + HELPER + s[insert_at:]
    s = s.replace(old, new)
    open(path, "w", encoding="utf-8").write(s)
    print(tag, "patched", "len", len(s))
    return True


ok = 0
for f in NEWS_FILES:
    if patch_file(os.path.join(ROOT, f), OLD_NEWS, NEW_NEWS, f):
        ok += 1

# Jobs: find files with naive job page loops
# Pattern variants: y(jobPages / for p in N pages buttons
for f in os.listdir(ROOT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "goJobPage" not in s:
        continue
    print("JOB_CAND", f, "len", len(s))
    # Look for v-for over page count
    # Common: y(ne, or y(oe, where ne=jobPages computed returning last_page number
    # Search button loop over full pages
    idx = s.find("goJobPage")
    print(s[max(0, idx - 100) : idx + 400])

print("news_ok", ok)
# verify
s = open(os.path.join(ROOT, "CfDteVJo.js"), encoding="utf-8").read()
print("verify_cpi", "function __cpi(" in s)
print("verify_ellipsis", "pg-ellipsis" in s)
print("verify_old_gone", OLD_NEWS not in s)
