#!/usr/bin/env python3
"""
Final pending-filter fix for live DLdwU6hU.js (when full SPA rebuild unavailable).
Mirrors source: URL-first listQuery, no double refresh on fullPath, status select -> navigateTo,
seq guard, client-side status filter on items.
"""
from pathlib import Path
import re

chunk = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s = chunk.read_text(encoding="utf-8", errors="ignore")
orig = s

# 1) Replace Ol() with URL-first version
ol_pat = re.compile(r"function Ol\(\)\{/\*pendingFix2\*/.*?return s\}", re.S)
# broader: from function Ol to return s}
if "function Ol(){" not in s:
    raise SystemExit("Ol missing")

# Find Ol function by marker or start
start = s.find("function Ol(){")
if start < 0:
    raise SystemExit("Ol start missing")
# find matching end at "return s}" after start (first occurrence of return s} related to Ol)
end = s.find("return s}", start)
if end < 0:
    raise SystemExit("Ol end missing")
end = end + len("return s}")

new_ol = r'''function Ol(){/*pendingFinal*/
function _q(k){const v=d.query[k];if(v!=null&&v!=="")return String(Array.isArray(v)?v[0]:v);try{return new URLSearchParams(location.search).get(k)||""}catch(e){return ""}}
const _es=_q("status")||c.value||"";
const _ev=_q("vip")||a.value||"";
const _em=_q("module")||v.value||"";
const _ep=_q("position")||C.value||"";
const _eg=_q("group")||V.value||"";
if(_es)c.value=_es;
const s={q:f.value||void 0,status:_es||void 0,vip:_ev||void 0,module:_em||void 0,position:_eg?void 0:_ep||void 0,group:_eg||void 0,page:z.value,sort:E.value||void 0,order:T.value||void 0};
we.value&&!ge.value&&Object.entries(D).forEach(([n,b])=>{b!==""&&b!=null&&(s[n]=b)});
Object.entries(Y).forEach(([n,b])=>{b!==""&&b!=null&&(s["cf_"+n]=b)});
return s}'''

s = s[:start] + new_ol + s[end:]
print("patched Ol")

# 2) Replace useAsyncData fetcher if still old or previous patch
# Look for window.__artListSeq or ()=>o(`/admin/resources/
if "window.__artListSeq" in s:
    # replace existing async fetcher body - find async()=>{if(typeof window.__artListSeq
    m = re.search(r"async\(\)=>\{if\(typeof window\.__artListSeq!=='number'\)window\.__artListSeq=0;.*?return res;\}", s)
    if not m:
        # try shorter unique start
        i = s.find("async()=>{if(typeof window.__artListSeq")
        if i < 0:
            raise SystemExit("artListSeq fetcher missing")
        # find end return res;}
        j = s.find("return res;}", i)
        if j < 0:
            raise SystemExit("fetcher end missing")
        j += len("return res;}")
        old_fetch = s[i:j]
    else:
        old_fetch = m.group(0)
        i = m.start()
        j = m.end()
else:
    old_fetch = "()=>o(`/admin/resources/${r.value}`,{query:Ol()})"
    if old_fetch not in s:
        raise SystemExit("plain fetcher missing")
    i = s.find(old_fetch)
    j = i + len(old_fetch)

new_fetch = (
    "async()=>{"
    "if(typeof window.__artListSeq!=='number')window.__artListSeq=0;"
    "const my=++window.__artListSeq;"
    "const qobj=Ol();"
    "const res=await o(`/admin/resources/${r.value}`,{query:qobj});"
    "if(my!==window.__artListSeq)return Dt.value??res;"
    "if(qobj&&qobj.status&&Array.isArray(res&&res.data)){"
    "const want=String(qobj.status);"
    "const filtered=res.data.filter(x=>String((x&&x.status)||'')===want);"
    "if(filtered.length!==res.data.length)return Object.assign({},res,{data:filtered});"
    "}"
    "return res;}"
)
s = s[:i] + new_fetch + s[j:]
print("patched fetcher")

# 3) Items filter
items_old_markers = [
    "Dl=w(()=>{const rows=Dt.value?.data||[];let want='';try{const qs=d.query.status;want=(qs==null||qs===''?(new URLSearchParams(location.search).get('status')||''):String(Array.isArray(qs)?qs[0]:qs))||c.value||'';}catch(e){want=c.value||''}return r.value==='articles'&&want?rows.filter(x=>String((x&&x.status)||'')===String(want)):rows})",
    "Dl=w(()=>Dt.value?.data||[])",
]
new_items = (
    "Dl=w(()=>{const rows=Dt.value?.data||[];"
    "let want='';"
    "try{const v=d.query.status;want=(v!=null&&v!==''?String(Array.isArray(v)?v[0]:v):(new URLSearchParams(location.search).get('status')||''))||c.value||''}catch(e){want=c.value||''}"
    "return want?rows.filter(x=>String((x&&x.status)||'')===String(want)):rows})"
)
replaced_items = False
for old in items_old_markers:
    if old in s:
        s = s.replace(old, new_items, 1)
        replaced_items = True
        print("patched items", old[:40])
        break
if not replaced_items:
    if "want?rows.filter(x=>String((x&&x.status)" in s:
        print("items already final-ish")
    else:
        print("WARN items not patched")

# 4) fullPath watch: remove pe() double-refresh
# ce(()=>d.fullPath,()=>{ae(),pe(),q.value=!1,...
old_fp = "ce(()=>d.fullPath,()=>{ae(),pe(),q.value=!1,Z.value=null,Object.keys(S).forEach(s=>delete S[s]),zl()},{flush:\"sync\"})"
new_fp = "ce(()=>d.fullPath,()=>{ae(),q.value=!1,Z.value=null,Object.keys(S).forEach(s=>delete S[s]),zl()},{flush:\"sync\"})"
if old_fp in s:
    s = s.replace(old_fp, new_fp, 1)
    print("patched fullPath: removed pe()")
elif "ce(()=>d.fullPath,()=>{ae(),q.value=!1" in s:
    print("fullPath already without pe")
else:
    print("WARN fullPath pattern missing")

# 5) Status select -> Be navigateTo URL sync
# Replace any previous status select handlers
sel_patterns = [
    (
        '"onUpdate:modelValue":n[1]||(n[1]=p=>{'
        'const next=p==null||p===""?"":String(p);'
        'fe(c)?c.value=next:null;'
        'z.value=1;X.value=[];'
        'const q=Object.assign({},d.query);'
        'if(next)q.status=next;else delete q.status;'
        'Be({path:d.path,query:q,replace:!0});'
        'fetch("http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508",{method:"POST",headers:{"Content-Type":"application/json","X-Debug-Session-Id":"d7e34e"},body:JSON.stringify({sessionId:"d7e34e",runId:"post-fix",hypothesisId:"E",location:"chunk:status-change",message:"status dropdown synced to URL",data:{next,path:d.path,queryStatus:q.status||null},timestamp:Date.now()})}).catch(()=>{});'
        '}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
    ),
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{fe(c)?c.value=p:null;Je&&Je()}),clearable:"",placeholder:"全部状态",class:"filter-sel"',
    '"onUpdate:modelValue":n[1]||(n[1]=p=>fe(c)?c.value=p:null),clearable:"",placeholder:"全部状态",class:"filter-sel"',
]
new_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{'
    'const next=p==null||p===""?"":String(p);'
    'fe(c)?c.value=next:null;z.value=1;X.value=[];'
    'const q=Object.assign({},d.query);'
    'if(next)q.status=next;else delete q.status;'
    'Be({path:d.path,query:q,replace:!0});'
    '}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)
sel_ok = False
for old in sel_patterns:
    if old in s:
        s = s.replace(old, new_sel, 1)
        sel_ok = True
        print("patched status select")
        break
if not sel_ok:
    if "Be({path:d.path,query:q,replace:!0})" in s and 'placeholder:"全部状态"' in s:
        print("status select likely already synced")
    else:
        print("WARN status select missing")

# 6) Show status select even when URL locked: remove te (routeLockedStatus) guard if present
# Pattern: e(se).status&&!e(te)&&!e(ye) or similar
# In source was: metaCfg?.status && !routeLockedStatus && !isHonors
# Live may be: e(se).status&&!e(te)&&!e(...)
s2, n = re.subn(
    r'(e\(se\)\.status&&)!e\(te\)&&',
    r'\1',
    s,
    count=3,
)
if n:
    s = s2
    print("removed routeLockedStatus hide on select", n)
else:
    print("note: locked-status hide pattern not found (ok if already open)")

if s == orig:
    print("NO CHANGES?")
else:
    chunk.write_text(s, encoding="utf-8")
    print("wrote", chunk, "delta", len(s) - len(orig))

# Remove noisy debug hook from HTML (optional keep for now - disable by emptying)
dbg = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/admin-pending-debug.js")
dbg.write_text("/* pending filter debug disabled after final fix */\n", encoding="utf-8")
print("silenced debug js")

# Strip PHP agent log
php = Path("/www/wwwroot/g.aifeili.cn/app/controller/api/v1/AdminResource.php")
pt = php.read_text(encoding="utf-8")
if "/*agentlog-pending*/" in pt:
    pt2 = re.sub(
        r"\n\s*/\*agentlog-pending\*/.*?/\*agentlog-pending-end\*/",
        "",
        pt,
        count=1,
        flags=re.S,
    )
    php.write_text(pt2, encoding="utf-8")
    print("removed php agent log")

print("ok")
